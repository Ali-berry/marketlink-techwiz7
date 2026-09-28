<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Market extends Model
{
    // Texas ke timezones - El Paso Mountain, baqi Central. Admin form ka dropdown bhi yahi hai
    public const TIMEZONES = [
        'America/Chicago' => ['name' => 'Central Time', 'short' => 'CT'],
        'America/Denver' => ['name' => 'Mountain Time', 'short' => 'MT'],
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'latitude',
        'longitude',
        'operating_days',
        'opens_at',
        'closes_at',
        'timezone',
        'cover_image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'operating_days' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function farmers(): BelongsToMany
    {
        return $this->belongsToMany(FarmerProfile::class, 'farmer_market')
            ->withPivot('stall_number')
            ->withTimestamps();
    }

    public function pickupWindows(): HasMany
    {
        return $this->hasMany(PickupWindow::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // cards pe "Saturday, Sunday"
    public function operatingDaysText(): string
    {
        return collect($this->operating_days)->map(fn ($day) => ucfirst($day))->join(', ');
    }

    // market ki jagah ka abhi ka time - opening hours aur cutoff ke saare checks isi se, app ka now() nahi
    public function localNow(): Carbon
    {
        return now($this->timezone);
    }

    // pickup date + slot time ko market ke timezone mein asal moment banata hai,
    // taake server kahin bhi ho now() se comparison sahi rahe
    public function pickupStartsAt(Carbon|string $pickupDate, string $startsAt): Carbon
    {
        $pickupDateString = $pickupDate instanceof Carbon ? $pickupDate->toDateString() : $pickupDate;

        return Carbon::parse($pickupDateString.' '.$startsAt, $this->timezone);
    }

    // "CT" ya "MT", pickup time ke saath
    public function timezoneLabel(): string
    {
        return self::TIMEZONES[$this->timezone]['short'] ?? $this->timezone;
    }

    public function isOpenNow(): bool
    {
        $marketLocalTime = $this->localNow();
        $todayName = strtolower($marketLocalTime->format('l'));

        if (! in_array($todayName, $this->operating_days ?? [], true)) {
            return false;
        }

        $currentTime = $marketLocalTime->format('H:i:s');

        return $currentTime >= $this->opens_at && $currentTime < $this->closes_at;
    }

    public function timingText(): string
    {
        return Carbon::parse($this->opens_at)->format('g:i A').' - '.Carbon::parse($this->closes_at)->format('g:i A');
    }

    // seeded photos public/images mein, admin ki upload ki hui storage disk pe
    public function coverImageUrl(): string
    {
        if (! $this->cover_image_path) {
            return asset('images/site/market-fallback.svg');
        }

        if (Str::startsWith($this->cover_image_path, 'images/')) {
            return asset($this->cover_image_path);
        }

        return Storage::disk('public')->url($this->cover_image_path);
    }

    // detail page ka banner card se chaura hai, is liye apni photos ki "-wide" copy hai.
    // upload ki hui cover ki copy nahi hoti, wo normal cover use karti hai
    public function bannerImageUrl(): string
    {
        if (! $this->cover_image_path || ! Str::startsWith($this->cover_image_path, 'images/')) {
            return $this->coverImageUrl();
        }

        $widePhotoPath = preg_replace('/\.webp$/', '-wide.webp', $this->cover_image_path);

        return file_exists(public_path($widePhotoPath))
            ? asset($widePhotoPath)
            : $this->coverImageUrl();
    }

    // is market tak OpenStreetMap directions
    public function directionsUrl(): string
    {
        return "https://www.openstreetmap.org/directions?to={$this->latitude}%2C{$this->longitude}";
    }
}
