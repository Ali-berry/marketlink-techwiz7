<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickupWindow extends Model
{
    protected $fillable = [
        'farmer_profile_id',
        'market_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'max_orders',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    private const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // slot form ke day dropdown ke liye
    public static function dayNames(): array
    {
        return self::DAY_NAMES;
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_profile_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function dayName(): string
    {
        return self::DAY_NAMES[$this->day_of_week];
    }

    public function timeRangeText(): string
    {
        return Carbon::parse($this->starts_at)->format('g:i A').' - '.Carbon::parse($this->ends_at)->format('g:i A');
    }

    // is weekly slot ki sab se pehli book ho sakne wali date: aaj, agar aaj slot ka din hai aur cutoff
    // nahi guzra (market ke time mein), warna agle hafte. AI assistant aur fallback isay use karte hain
    public function nextBookableDate(int $cutoffHours): Carbon
    {
        $marketToday = $this->market->localNow()->startOfDay();

        $pickupDate = $marketToday->dayOfWeek === $this->day_of_week
            ? $marketToday
            : $marketToday->copy()->next($this->day_of_week);

        if (! Order::isWithinCutoff($this->market, $pickupDate, $this->starts_at, $cutoffHours)) {
            $pickupDate->addWeek();
        }

        return $pickupDate;
    }
}
