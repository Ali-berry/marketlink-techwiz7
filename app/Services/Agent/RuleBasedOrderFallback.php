<?php

namespace App\Services\Agent;

use App\Enums\OrderPlacedVia;
use App\Exceptions\BasketCheckoutException;
use App\Models\Market;
use App\Models\PickupWindow;
use App\Models\Product;
use App\Models\User;
use App\Services\PlaceOrderFromBasket;
use Illuminate\Support\Str;

// Jab Groq ki dono keys fail hon tab ka aakhri rasta - sirf ek shape ka message samajhta hai:
// "<qty> <unit> <product> <market> <day>". Har cheez ka exactly ek match chahiye, warna null,
// kyunki galat order lagane se behtar hai ke bata dein AI band hai. Sirf customer ke liye.
class RuleBasedOrderFallback
{
    private const UNIT_WORDS = [
        'kg' => 'kg', 'kgs' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
        'dozen' => 'dozen', 'dozens' => 'dozen',
        'bunch' => 'bunch', 'bunches' => 'bunch',
        'piece' => 'piece', 'pieces' => 'piece', 'pcs' => 'piece',
        'litre' => 'litre', 'litres' => 'litre', 'liter' => 'litre', 'liters' => 'litre',
        'pack' => 'pack', 'packs' => 'pack',
    ];

    private const DAY_NUMBERS = [
        'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
        'thursday' => 4, 'friday' => 5, 'saturday' => 6,
    ];

    public function __construct(private readonly PlaceOrderFromBasket $placeOrderFromBasket)
    {
    }

    public function tryPlaceOrder(User $customer, string $message): ?string
    {
        $quantity = $this->extractQuantity($message);
        $product = $quantity ? $this->matchSingleProduct($message) : null;
        $market = $product ? $this->matchSingleMarket($message) : null;
        $dayOfWeek = $market ? $this->extractDayOfWeek($message) : null;

        if (! $quantity || ! $product || ! $market || $dayOfWeek === null) {
            return null;
        }

        $pickupWindow = PickupWindow::where('farmer_profile_id', $product->farmer_profile_id)
            ->where('market_id', $market->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $pickupWindow) {
            return null;
        }

        return $this->placeOrder($customer, $product, $pickupWindow, $quantity);
    }

    private function placeOrder(User $customer, Product $product, PickupWindow $pickupWindow, int $quantity): string
    {
        // cutoff se pehle wali agli date, market ke timezone mein
        $pickupDate = $pickupWindow->nextBookableDate($product->farmer->order_cutoff_hours);

        try {
            // sirf ye ek product - basket ko haath nahi lagta
            $order = $this->placeOrderFromBasket->placeSingleProduct(
                customer: $customer,
                product: $product,
                quantity: $quantity,
                pickupWindow: $pickupWindow,
                pickupDate: $pickupDate,
                // customer ne assistant mein hi likha tha, is liye ye bhi AI chat ka order hai
                placedVia: OrderPlacedVia::AiChat,
            );
        } catch (BasketCheckoutException $exception) {
            // out of stock / slot full jaisi asal wajah generic error se behtar hai
            return $exception->getMessage();
        }

        return "Your order is confirmed (the assistant is briefly unavailable, so it was booked directly): ".
            "{$order->order_number} - {$product->name} x{$quantity} from {$product->farmer->stall_name}, ".
            "pickup at {$pickupWindow->market->name} on {$pickupDate->format('l j M')}, ".
            "{$pickupWindow->timeRangeText()} {$pickupWindow->market->timezoneLabel()}.";
    }

    private function extractQuantity(string $message): ?int
    {
        $unitPattern = implode('|', array_map(fn (string $word) => preg_quote($word, '/'), array_keys(self::UNIT_WORDS)));

        if (! preg_match('/(\d+(?:\.\d+)?)\s*('.$unitPattern.')\b/i', $message, $matches)) {
            return null;
        }

        return (int) round((float) $matches[1]);
    }

    // "tomato" ko "Vine tomatoes" se match kar leta hai (akhri "s" ignore).
    // Ek se zyada product match hon to null, guess nahi karte
    private function matchSingleProduct(string $message): ?Product
    {
        $lowerMessage = Str::lower($message);

        $matches = Product::visibleToCustomers()->with('farmer')->get()
            ->filter(fn (Product $product) => $this->keyWordAppearsIn($product->name, $lowerMessage));

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function matchSingleMarket(string $message): ?Market
    {
        $lowerMessage = Str::lower($message);

        $matches = Market::active()->get()
            ->filter(fn (Market $market) => Str::contains($lowerMessage, Str::lower($market->name)));

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function keyWordAppearsIn(string $name, string $lowerMessage): bool
    {
        $words = explode(' ', Str::lower($name));
        $keyWord = end($words);

        // Str::singular asli English rules jaanta hai, rtrim('s') "tomatoes" ko "tomatoe" bana deta
        return Str::contains($lowerMessage, $keyWord)
            || Str::contains($lowerMessage, Str::singular($keyWord))
            || Str::contains($lowerMessage, Str::plural($keyWord));
    }

    private function extractDayOfWeek(string $message): ?int
    {
        $lowerMessage = Str::lower($message);

        foreach (self::DAY_NUMBERS as $dayName => $dayNumber) {
            if (Str::contains($lowerMessage, $dayName)) {
                return $dayNumber;
            }
        }

        return null;
    }
}
