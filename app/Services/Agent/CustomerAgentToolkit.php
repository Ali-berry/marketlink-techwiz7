<?php

namespace App\Services\Agent;

use App\Enums\OrderPlacedVia;
use App\Enums\OrderStatus;
use App\Exceptions\BasketCheckoutException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Helpers\MoneyFormatter;
use App\Models\Conversation;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Order;
use App\Models\PickupWindow;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderStatusUpdater;
use App\Services\PlaceOrderFromBasket;
use App\Services\UrgentOrderService;
use Carbon\Carbon;

// Customer AI ke tools - har tool wahi service call karta hai jo asal page karta hai,
// is liye stock lock aur slot capacity wale rules yahan bhi lagte hain. Basket ko haath nahi lagta.
// Naya field AI tak khud nahi pohanchta, neeche wale result array mein add karna parta hai.
class CustomerAgentToolkit
{
    public function __construct(
        private readonly PlaceOrderFromBasket $placeOrderFromBasket,
        private readonly OrderStatusUpdater $orderStatusUpdater,
        private readonly UrgentOrderService $urgentOrderService,
    ) {
    }

    public function schemas(User $actor): array
    {
        return [
            $this->schema('search_products', 'Search for products a customer can currently order.', [
                'query' => ['type' => 'string', 'description' => 'Product name or keyword to search for, e.g. "tomatoes"'],
                'category' => ['type' => 'string', 'description' => 'Category name to filter by, e.g. "vegetables"'],
                'max_price' => ['type' => 'number', 'description' => 'Only show products at or under this price'],
            ], []),

            $this->schema('get_market_info', 'Get details about one market, or every active market if none is specified - including each farmer\'s pickup windows and their ids. Always call this before place_preorder to find the right pickup_window_id.', [
                'market_id' => ['type' => 'integer', 'description' => 'The market\'s id, if the customer named a specific market'],
            ], []),

            $this->schema('get_my_orders', 'List the customer\'s own pre-orders, optionally filtered by status.', [
                'status' => ['type' => 'string', 'description' => 'One of: placed, accepted, ready_for_pickup, completed, declined, cancelled'],
            ], []),

            $this->schema('place_preorder', 'Place a real pre-order for one product from one farmer, for a specific pickup window.', [
                'product_name' => ['type' => 'string', 'description' => 'Name of the product to order'],
                'quantity' => ['type' => 'integer', 'description' => 'How many units to order'],
                'pickup_window_id' => ['type' => 'integer', 'description' => 'The id of the pickup window to collect it at - get this from get_market_info first'],
            ], ['product_name', 'quantity', 'pickup_window_id']),

            $this->schema('find_urgent_pickup', 'Find farmers who can hand over a product at their own stall within the next hour or two - use this whenever the customer needs something urgently or right now.', [
                'product_name' => ['type' => 'string', 'description' => 'Product the customer needs, e.g. "tomatoes"'],
            ], ['product_name']),

            $this->schema('place_urgent_order', 'Place a real urgent order for pickup at the farmer\'s own stall in 15 to 120 minutes. Only after find_urgent_pickup, and only once the customer said yes.', [
                'farmer_name' => ['type' => 'string', 'description' => 'Stall name of the farmer, from find_urgent_pickup'],
                'product_name' => ['type' => 'string', 'description' => 'Name of the product to order'],
                'quantity' => ['type' => 'integer', 'description' => 'How many units to order'],
                'pickup_in_minutes' => ['type' => 'integer', 'description' => 'Minutes from now the customer will pick it up, between 15 and 120'],
            ], ['farmer_name', 'product_name', 'quantity', 'pickup_in_minutes']),

            $this->schema('cancel_order', 'Cancel one of the customer\'s own upcoming orders.', [
                'order_id' => ['type' => 'integer', 'description' => 'The id of the order to cancel'],
            ], ['order_id']),

            $this->schema('add_favourite', 'Save a farmer or a product to the customer\'s favourites.', [
                'farmer_name' => ['type' => 'string', 'description' => 'Stall name of the farmer to favourite'],
                'product_name' => ['type' => 'string', 'description' => 'Name of the product to favourite'],
            ], []),

            $this->schema('message_farmer', 'Send a message to a farmer\'s stall on the customer\'s behalf.', [
                'farmer_name' => ['type' => 'string', 'description' => 'Stall name of the farmer to message'],
                'message_text' => ['type' => 'string', 'description' => 'The message to send'],
            ], ['farmer_name', 'message_text']),
        ];
    }

    public function execute(User $actor, string $toolName, array $arguments): array
    {
        return match ($toolName) {
            'search_products' => $this->searchProducts($arguments),
            'get_market_info' => $this->getMarketInfo($arguments),
            'get_my_orders' => $this->getMyOrders($actor, $arguments),
            'place_preorder' => $this->placePreorder($actor, $arguments),
            'find_urgent_pickup' => $this->findUrgentPickup($actor, $arguments),
            'place_urgent_order' => $this->placeUrgentOrder($actor, $arguments),
            'cancel_order' => $this->cancelOrder($actor, $arguments),
            'add_favourite' => $this->addFavourite($actor, $arguments),
            'message_farmer' => $this->messageFarmer($actor, $arguments),
            default => ['error' => "Unknown tool: {$toolName}"],
        };
    }

    private function searchProducts(array $arguments): array
    {
        $products = Product::visibleToCustomers()
            ->with('farmer')
            ->when($arguments['query'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when($arguments['category'] ?? null, fn ($query, $category) => $query
                ->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$category}%")))
            ->when($arguments['max_price'] ?? null, fn ($query, $maxPrice) => $query->where('price', '<=', $maxPrice))
            ->take(10)
            ->get();

        return [
            'count' => $products->count(),
            'products' => $products->map(fn (Product $product) => [
                'name' => $product->name,
                'price' => (float) $product->price,
                'price_text' => MoneyFormatter::format($product->price),
                'unit' => $product->unit,
                'stock_quantity' => $product->stock_quantity,
                'farmer_name' => $product->farmer->stall_name,
                'availability' => $product->availability->label(),
            ])->all(),
        ];
    }

    private function getMarketInfo(array $arguments): array
    {
        $markets = Market::active()
            ->with(['farmers' => fn ($farmerQuery) => $farmerQuery->approved()])
            ->when($arguments['market_id'] ?? null, fn ($query, $marketId) => $query->whereKey($marketId))
            ->get();

        if ($markets->isEmpty()) {
            return ['error' => 'No matching market found.'];
        }

        return [
            'markets' => $markets->map(fn (Market $market) => [
                'market_id' => $market->id,
                'name' => $market->name,
                'address' => $market->address,
                'open_days' => $market->operatingDaysText(),
                'timings' => $market->timingText(),
                // place_preorder ke liye pickup_window_id sirf yahin se milti hai
                'farmers' => $market->farmers->map(fn (FarmerProfile $farmer) => [
                    'farmer_name' => $farmer->stall_name,
                    'pickup_windows' => $farmer->pickupWindows()
                        ->where('market_id', $market->id)
                        ->where('is_active', true)
                        ->get()
                        ->map(fn (PickupWindow $window) => [
                            'pickup_window_id' => $window->id,
                            'day' => $window->dayName(),
                            'time' => $window->timeRangeText(),
                        ])->all(),
                ])->all(),
            ])->all(),
        ];
    }

    private function getMyOrders(User $actor, array $arguments): array
    {
        $orders = $actor->ordersAsCustomer()
            ->with(['farmer', 'market'])
            ->when($arguments['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('pickup_date')
            ->take(10)
            ->get();

        return [
            'count' => $orders->count(),
            'orders' => $orders->map(fn (Order $order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'farmer_name' => $order->farmer->stall_name,
                'pickup_date' => $order->pickup_date->toFormattedDateString(),
                'status' => $order->status->label(),
                'total_amount' => (float) $order->total_amount,
                'total_text' => MoneyFormatter::format($order->total_amount),
            ])->all(),
        ];
    }

    private function placePreorder(User $actor, array $arguments): array
    {
        $pickupWindow = PickupWindow::where('is_active', true)->with('market')->find($arguments['pickup_window_id']);

        if (! $pickupWindow) {
            return ['error' => 'That pickup window doesn\'t exist. Call get_market_info to see valid pickup windows first.'];
        }

        // slot se farmer pata hai, to sirf usi ke products dekho
        $product = AgentNameMatcher::findOne(
            Product::visibleToCustomers()->where('farmer_profile_id', $pickupWindow->farmer_profile_id),
            'name',
            $arguments['product_name'],
            "product at this farmer's stall",
        );

        if (is_array($product)) {
            return $product;
        }

        // cutoff se pehle wali agli date, market ke timezone mein
        $pickupDate = $pickupWindow->nextBookableDate($product->farmer->order_cutoff_hours);

        try {
            // sirf ye ek product - basket waisi hi rehti hai
            $order = $this->placeOrderFromBasket->placeSingleProduct(
                customer: $actor,
                product: $product,
                quantity: (int) $arguments['quantity'],
                pickupWindow: $pickupWindow,
                pickupDate: $pickupDate,
                placedVia: OrderPlacedVia::AiChat,
            );
        } catch (BasketCheckoutException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return [
            'success' => true,
            'order_number' => $order->order_number,
            'farmer_name' => $product->farmer->stall_name,
            'pickup_date' => $pickupDate->toFormattedDateString(),
            'pickup_time' => $pickupWindow->timeRangeText(),
            'total_amount' => (float) $order->total_amount,
            'total_text' => MoneyFormatter::format($order->total_amount),
        ];
    }

    private function findUrgentPickup(User $actor, array $arguments): array
    {
        $urgentOptions = $this->urgentOrderService->findAvailableNow($arguments['product_name'], $actor);

        if ($urgentOptions->isEmpty()) {
            return [
                'count' => 0,
                'message' => 'No farmer can do an urgent pickup of that right now. Offer a normal pre-order for the next market day instead.',
            ];
        }

        return [
            'count' => $urgentOptions->count(),
            'options' => $urgentOptions->map(fn (array $option) => [
                'farmer_name' => $option['farmer']->stall_name,
                'product_name' => $option['product']->name,
                'price' => $option['price'],
                'price_text' => MoneyFormatter::format($option['price']),
                'unit' => $option['product']->unit,
                'stock_quantity' => $option['stock'],
                'pickup_address' => $option['pickup_address'],
                'distance_miles' => $option['distance_in_miles'],
                'confirmation' => $option['auto_confirms'] ? 'confirms instantly' : 'farmer will confirm',
                'urgent_hours' => $option['farmer']->urgentHoursText(),
                // AI ko ghari ka time guess na karna pare, wo sirf "X minute baad" mein kaam kare
                'now' => $this->farmerLocalTimeText($option['farmer']),
            ])->all(),
        ];
    }

    private function placeUrgentOrder(User $actor, array $arguments): array
    {
        $farmer = AgentNameMatcher::findOne(FarmerProfile::acceptingUrgentOrders(), 'stall_name', $arguments['farmer_name'], 'farmer taking urgent orders');

        if (is_array($farmer)) {
            return $farmer;
        }

        $product = AgentNameMatcher::findOne(
            Product::visibleToCustomers()->where('farmer_profile_id', $farmer->id),
            'name',
            $arguments['product_name'],
            "product at {$farmer->stall_name}",
        );

        if (is_array($product)) {
            return $product;
        }

        try {
            $order = $this->urgentOrderService->placeUrgentOrder(
                $actor,
                $product,
                (int) $arguments['quantity'],
                (int) $arguments['pickup_in_minutes'],
                OrderPlacedVia::AiChat,
            );
        } catch (BasketCheckoutException $exception) {
            return ['error' => $exception->getMessage()];
        }

        // prep time 0 ho to order yahan tak ready bhi ho chuka hota hai
        $wasAutoConfirmed = in_array($order->status, [OrderStatus::Accepted, OrderStatus::ReadyForPickup], true);

        return [
            'success' => true,
            'order_number' => $order->order_number,
            'farmer_name' => $farmer->stall_name,
            'pickup_by' => $order->urgentPickupTimeText().' '.$order->market->timezoneLabel(),
            'pickup_address' => $farmer->address,
            'total_amount' => (float) $order->total_amount,
            'total_text' => MoneyFormatter::format($order->total_amount),
            'status' => $wasAutoConfirmed
                ? 'Confirmed by MarketLink AI - the farmer knows the customer is coming'
                : 'Sent to the farmer, waiting for them to confirm',
            'cancel_rule' => 'Can only be cancelled in the first '.Order::URGENT_CANCEL_GRACE_MINUTES.' minutes',
            'ready_update' => $this->urgentReadyUpdateText($order, $wasAutoConfirmed),
        ];
    }

    // farmer ne AI ready wali setting on ki ho to customer ko kya batana hai, warna null
    private function urgentReadyUpdateText(Order $order, bool $wasAutoConfirmed): ?string
    {
        if (! $wasAutoConfirmed || ! $order->farmer->ai_marks_urgent_ready) {
            return null;
        }

        if ($order->status === OrderStatus::ReadyForPickup) {
            return 'Already marked ready by the farmer\'s AI - the customer can come now';
        }

        return 'Farmer\'s AI will mark it ready in '.$order->farmer->urgent_prep_minutes.' minutes';
    }

    // "4:06 PM CT" - farmer ki jagah ka abhi ka time
    private function farmerLocalTimeText(FarmerProfile $farmer): string
    {
        return trim($farmer->localNow()->format('g:i A').' '.$farmer->homeMarket()?->timezoneLabel());
    }

    private function cancelOrder(User $actor, array $arguments): array
    {
        $order = $actor->ordersAsCustomer()->find($arguments['order_id']);

        if (! $order) {
            return ['error' => "That order doesn't belong to this account."];
        }

        try {
            $this->orderStatusUpdater->transition($order, OrderStatus::Cancelled, $actor);
        } catch (InvalidOrderTransitionException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return ['success' => true, 'order_number' => $order->order_number];
    }

    private function addFavourite(User $actor, array $arguments): array
    {
        if ($farmerName = $arguments['farmer_name'] ?? null) {
            $farmer = AgentNameMatcher::findOne(FarmerProfile::approved(), 'stall_name', $farmerName, 'farmer');

            if (is_array($farmer)) {
                return $farmer;
            }

            $actor->favouriteFarmers()->syncWithoutDetaching([$farmer->id]);

            return ['success' => true, 'favourited' => $farmer->stall_name];
        }

        if ($productName = $arguments['product_name'] ?? null) {
            $product = AgentNameMatcher::findOne(Product::visibleToCustomers(), 'name', $productName, 'product');

            if (is_array($product)) {
                return $product;
            }

            $actor->favouriteProducts()->syncWithoutDetaching([$product->id => ['wants_restock_alert' => true]]);

            return ['success' => true, 'favourited' => $product->name];
        }

        return ['error' => 'Tell me which farmer or product to favourite.'];
    }

    private function messageFarmer(User $actor, array $arguments): array
    {
        $farmer = AgentNameMatcher::findOne(FarmerProfile::approved(), 'stall_name', $arguments['farmer_name'], 'farmer');

        if (is_array($farmer)) {
            return $farmer;
        }

        // sirf customer hi thread shuru kar sakta hai, AI se bheje ya khud
        $conversation = Conversation::firstOrCreate([
            'customer_id' => $actor->id,
            'farmer_profile_id' => $farmer->id,
        ]);

        $conversation->messages()->create([
            'sender_id' => $actor->id,
            'body' => $arguments['message_text'],
        ]);

        return ['success' => true, 'sent_to' => $farmer->stall_name];
    }

    private function schema(string $name, string $description, array $properties, array $required): array
    {
        return [
            'permission' => null,
            'schema' => [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $description,
                    'parameters' => [
                        'type' => 'object',
                        // empty array json mein [] banta hai {} nahi, Groq ye reject karta hai
                        'properties' => $this->allowNullOnOptionalProperties($properties, $required) ?: (object) [],
                        'required' => $required,
                    ],
                ],
            ],
        ];
    }

    // model skip kiye hue param pe null bhej deta hai (jaise market_id: null) aur Groq strict schema
    // pe fail kar deta hai, is liye har optional param ka type null bhi allow karta hai
    private function allowNullOnOptionalProperties(array $properties, array $required): array
    {
        foreach ($properties as $propertyName => &$propertySchema) {
            if (! in_array($propertyName, $required, true) && isset($propertySchema['type'])) {
                $propertySchema['type'] = [$propertySchema['type'], 'null'];
            }
        }

        return $properties;
    }
}
