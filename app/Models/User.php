<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

// MustVerifyEmail jaan boojh ke nahi - demo mein mail log driver pe hai, verify link kisi tak nahi pohanchta.
// register karte hi seedha dashboard
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    // moderate-community-posts ke ilawa saari admin permissions. In mein se koi ek ho to "general" admin -
    // sidebar, dashboard route aur dashboardRouteName() sab yahi list dekhte hain
    public const GENERAL_ADMIN_PERMISSIONS = [
        'manage-farmers',
        'manage-customers',
        'moderate-content',
        'manage-markets',
        'manage-categories',
        'manage-announcements',
        'view-reports',
    ];

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'role',
        'phone',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isFarmer(): bool
    {
        return $this->role === UserRole::Farmer;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    // proactive inbox aur admin agent toolkit dono "us permission wale sab admins" chahte hain
    public static function adminsWithPermission(string $permission): Collection
    {
        return static::where('role', UserRole::Admin->value)->get()
            ->filter(fn (self $admin) => $admin->can($permission))
            ->values();
    }

    // /dashboard kahan bheje. Community Moderator ke paas general admin permission nahi,
    // is liye us ka home community page hai
    public function dashboardRouteName(): string
    {
        if ($this->role === UserRole::Admin && ! $this->canAny(self::GENERAL_ADMIN_PERMISSIONS)) {
            return 'admin.community.index';
        }

        return $this->role->dashboardRouteName();
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    // nearest-market sorting ke liye location saved hai ya nahi - farmer/admin aksar set nahi karte
    public function hasSavedLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    // sirf farmer accounts ka hota hai
    public function farmerProfile(): HasOne
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function ordersAsCustomer(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    // conversation sirf customer shuru karta hai, is liye ye customer wali side hai
    public function conversationsAsCustomer(): HasMany
    {
        return $this->hasMany(Conversation::class, 'customer_id');
    }

    // saari conversations mein doosri taraf ke unread messages
    public function unreadMessageCount(): int
    {
        $conversationIds = $this->isFarmer()
            ? $this->farmerProfile?->conversations()->pluck('conversations.id') ?? collect()
            : $this->conversationsAsCustomer()->pluck('id');

        return Message::whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $this->id)
            ->whereNull('read_at')
            ->count();
    }

    public function favouriteFarmers(): BelongsToMany
    {
        return $this->belongsToMany(FarmerProfile::class, 'favourite_farmers', 'customer_id', 'farmer_profile_id')
            ->withTimestamps();
    }

    public function favouriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favourite_products', 'customer_id', 'product_id')
            ->withPivot('wants_restock_alert')
            ->withTimestamps();
    }

    public function savedMarkets(): BelongsToMany
    {
        return $this->belongsToMany(Market::class, 'saved_markets', 'customer_id', 'market_id')
            ->withTimestamps();
    }
}
