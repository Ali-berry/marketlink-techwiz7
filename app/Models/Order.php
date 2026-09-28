<?php

namespace App\Models;

use App\Enums\OrderPlacedVia;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    // urgent order sirf pehle 5 min mein cancel ho sakta hai
    public const URGENT_CANCEL_GRACE_MINUTES = 5;

    protected $fillable = [
        'order_number',
        'customer_id',
        'farmer_profile_id',
        'market_id',
        'pickup_window_id',
        'pickup_date',
        'is_urgent',
        'urgent_pickup_at',
        'status',
        'placed_via',
        'total_amount',
        'customer_note',
        'decline_reason',
        'accepted_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'placed_via' => OrderPlacedVia::class,
            'pickup_date' => 'date',
            'is_urgent' => 'boolean',
            'urgent_pickup_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'accepted_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // raw id ki jagah ML-7K2QX9 jaisa number
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ML-'.strtoupper(Str::random(6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_profile_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function pickupWindow(): BelongsTo
    {
        return $this->belongsTo(PickupWindow::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusChanges(): HasMany
    {
        // id se tie todte hain - auto-confirm pe "placed" aur "accepted" ek hi second mein hote hain
        return $this->hasMany(OrderStatusChange::class)->latest('created_at')->latest('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::openStatuses());
    }

    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('is_urgent', true);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Completed->value);
    }

    // customer tab tak cancel kar sakta hai jab order open ho aur farmer ka cutoff shuru na hua ho
    public function customerCanStillChange(): bool
    {
        if (! in_array($this->status, [OrderStatus::Placed, OrderStatus::Accepted], true)) {
            return false;
        }

        // farmer shayad pack kar raha ho, is liye urgent pe chhota grace period
        if ($this->is_urgent) {
            return $this->created_at->gt(now()->subMinutes(self::URGENT_CANCEL_GRACE_MINUTES));
        }

        // slot delete ho chuka ho to pickup din ki raat 12 baje maan lo
        $slotStartsAt = $this->pickupWindow?->starts_at ?? '00:00:00';

        return self::isWithinCutoff($this->market, $this->pickup_date, $slotStartsAt, $this->farmer->order_cutoff_hours);
    }

    public function wasPlacedViaAiChat(): bool
    {
        return $this->placed_via === OrderPlacedVia::AiChat;
    }

    // accepted urgent order jiske farmer ne AI ready on kiya ho aur prep time guzar gaya ho.
    // scheduler aur page load dono yahi rule use karte hain
    public function isDueForAutoReady(): bool
    {
        if (! $this->is_urgent || $this->status !== OrderStatus::Accepted || ! $this->accepted_at) {
            return false;
        }

        if (! $this->farmer->ai_marks_urgent_ready) {
            return false;
        }

        return $this->accepted_at->copy()->addMinutes($this->farmer->urgent_prep_minutes)->lte(now());
    }

    // "3:40 PM" farmer ke timezone mein
    public function urgentPickupTimeText(): string
    {
        return $this->urgent_pickup_at->copy()->setTimezone($this->market->timezone)->format('g:i A');
    }

    // "on Sat 3 Oct at Dallas Farmers Market", urgent pe "by 3:40 PM at 12 Farm Rd" - urgent pickup farm pe hota hai
    public function pickupSummary(): string
    {
        if ($this->is_urgent) {
            return 'by '.$this->urgentPickupTimeText().' at '.$this->farmer->address;
        }

        return 'on '.$this->pickup_date->format('D j M').' at '.$this->market->name;
    }

    // pickup din shuru hone se pehle farmer order completed nahi kar sakta (market ka timezone)
    public function pickupDayHasArrived(): bool
    {
        return $this->market->localNow()->toDateString() >= $this->pickup_date->toDateString();
    }

    // "2 kg carrots, 1 bunch spinach" - 3 se zyada items hon to pehle 2 + "and 2 more"
    public function itemsSummaryText(): string
    {
        $items = $this->items;
        $itemText = fn (OrderItem $item) => "{$item->quantity} {$item->unit} {$item->product_name}";

        if ($items->count() <= 3) {
            return $items->map($itemText)->join(', ', ' and ');
        }

        return $items->take(2)->map($itemText)->join(', ').' and '.($items->count() - 2).' more';
    }

    // "pickup se X ghante pehle cutoff" ka hisaab sirf yahan hai - order aur checkout dono yahi use karte hain.
    // time market ke timezone mein, El Paso ka 8 AM Dallas ke 8 AM se ek ghanta baad hai
    public static function isWithinCutoff(Market $market, Carbon|string $pickupDate, string $slotStartsAt, ?int $cutoffHours): bool
    {
        $cutoffHours ??= config('marketlink.default_order_cutoff_hours');

        $pickupStartsAt = $market->pickupStartsAt($pickupDate, $slotStartsAt);

        return $market->localNow()->lt($pickupStartsAt->subHours($cutoffHours));
    }
}
