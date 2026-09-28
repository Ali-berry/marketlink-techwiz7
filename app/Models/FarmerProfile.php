<?php

namespace App\Models;

use App\Enums\FarmerApprovalStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmerProfile extends Model
{
    // normal register aur Google sign-up dono yahi use karte hain taake stall URL same na ho
    public static function uniqueSlugFor(string $stallName): string
    {
        $baseSlug = Str::slug($stallName);
        $slug = $baseSlug;
        $suffix = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.++$suffix;
        }

        return $slug;
    }

    protected $fillable = [
        'user_id',
        'stall_name',
        'slug',
        'contact_person',
        'bio',
        'farming_experience',
        'address',
        'latitude',
        'longitude',
        'order_cutoff_hours',
        'accepts_urgent_orders',
        'ai_auto_confirms_urgent',
        'ai_marks_urgent_ready',
        'urgent_prep_minutes',
        'urgent_pickup_starts_at',
        'urgent_pickup_ends_at',
        'max_urgent_orders_per_hour',
        'approval_status',
        'approved_at',
        'suspension_reason',
        'cover_image_path',
    ];

    protected function casts(): array
    {
        return [
            'approval_status' => FarmerApprovalStatus::class,
            'approved_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'accepts_urgent_orders' => 'boolean',
            'ai_auto_confirms_urgent' => 'boolean',
            'ai_marks_urgent_ready' => 'boolean',
            'urgent_prep_minutes' => 'integer',
            'max_urgent_orders_per_hour' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markets(): BelongsToMany
    {
        return $this->belongsToMany(Market::class, 'farmer_market')
            ->withPivot('stall_number')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function pickupWindows(): HasMany
    {
        return $this->hasMany(PickupWindow::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === FarmerApprovalStatus::Approved;
    }

    public function hasMapLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    // stall ke pin tak OpenStreetMap directions
    public function directionsUrl(): string
    {
        return "https://www.openstreetmap.org/directions?to={$this->latitude}%2C{$this->longitude}";
    }

    // urgent order isi market mein jata hai aur urgent hours isi ke timezone mein - farmer ki pehli active market
    public function homeMarket(): ?Market
    {
        return $this->markets()->active()->orderBy('farmer_market.id')->first();
    }

    // farmer ki jagah pe abhi ka time, Market::localNow() jaisa
    public function localNow(): Carbon
    {
        return now($this->homeMarket()?->timezone ?? config('app.timezone'));
    }

    // time farmer ke urgent hours mein hai ya nahi. Raat 12 ke paar wale hours (10 PM - 2 AM) bhi chalte hain
    public function urgentHoursInclude(Carbon $moment): bool
    {
        if (! $this->urgent_pickup_starts_at || ! $this->urgent_pickup_ends_at) {
            return false;
        }

        $timeOfDay = $moment->copy()->setTimezone($this->localNow()->timezone)->format('H:i:s');
        $startsAt = Carbon::parse($this->urgent_pickup_starts_at)->format('H:i:s');
        $endsAt = Carbon::parse($this->urgent_pickup_ends_at)->format('H:i:s');

        if ($startsAt <= $endsAt) {
            return $timeOfDay >= $startsAt && $timeOfDay <= $endsAt;
        }

        return $timeOfDay >= $startsAt || $timeOfDay <= $endsAt;
    }

    // "9:00 AM - 6:00 PM" - stall page aur AI ke jawab ke liye
    public function urgentHoursText(): string
    {
        if (! $this->urgent_pickup_starts_at || ! $this->urgent_pickup_ends_at) {
            return 'Not set';
        }

        return Carbon::parse($this->urgent_pickup_starts_at)->format('g:i A').' - '.Carbon::parse($this->urgent_pickup_ends_at)->format('g:i A');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', FarmerApprovalStatus::Approved->value);
    }

    // customer sirf approved farmers dekh sakta hai, pending / suspended hide
    public function scopeVisibleToCustomers(Builder $query): Builder
    {
        return $query->approved();
    }

    // urgent orders on wale farmers - hours aur stock alag check hote hain
    public function scopeAcceptingUrgentOrders(Builder $query): Builder
    {
        return $query->approved()->where('accepts_urgent_orders', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', FarmerApprovalStatus::Pending->value);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('approval_status', FarmerApprovalStatus::Suspended->value);
    }

    public function averageRating(): ?float
    {
        $average = $this->reviews()->where('is_hidden_by_admin', false)->avg('rating');

        return $average ? round($average, 1) : null;
    }

    // sab se zyada bikne wale, sirf completed orders se
    public function bestSellingProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->whereHas('order', fn (Builder $orderQuery) => $orderQuery
                ->where('farmer_profile_id', $this->id)
                ->completed())
            ->selectRaw('product_name, unit, SUM(quantity) as total_quantity_sold, SUM(line_total) as total_earned')
            ->groupBy('product_name', 'unit')
            ->orderByDesc('total_quantity_sold')
            ->take($limit)
            ->get();
    }

    // "Selling on MarketLink since March 2026 (3 months)" - platform pe kitna time, farming_experience alag cheez hai
    public function sellingSinceLabel(): string
    {
        // Carbon 3 yahan fraction deta hai (8.00004), is liye neeche round
        $monthsOnMarketLink = (int) floor($this->created_at->diffInMonths(now()));

        $durationText = match (true) {
            $monthsOnMarketLink < 1 => 'just joined',
            $monthsOnMarketLink === 1 => '1 month',
            default => "{$monthsOnMarketLink} months",
        };

        return 'Selling on MarketLink since '.$this->created_at->format('F Y').' ('.$durationText.')';
    }

    // seeded photos public/images mein, farmer ki upload ki hui storage disk pe
    public function coverImageUrl(): string
    {
        if (! $this->cover_image_path) {
            return asset('images/farmers/farmer-generic.webp');
        }

        if (Str::startsWith($this->cover_image_path, 'images/')) {
            return asset($this->cover_image_path);
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }
}
