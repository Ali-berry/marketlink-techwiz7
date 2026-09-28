<?php

namespace App\Models;

use App\Enums\ProductAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'farmer_profile_id',
        'product_category_id',
        'name',
        'slug',
        'description',
        'price',
        'unit',
        'stock_quantity',
        'weekly_default_quantity',
        'availability',
        'image_path',
        'is_hidden_by_admin',
        'hidden_reason',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'availability' => ProductAvailability::class,
            'is_hidden_by_admin' => 'boolean',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // jin customers ne favourite karke restock alert maanga
    public function customersWantingRestockAlert(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favourite_products', 'product_id', 'customer_id')
            ->wherePivot('wants_restock_alert', true);
    }

    // customer ko sirf ye dikhta hai: farmer approved, product hidden nahi, paused nahi
    public function scopeVisibleToCustomers(Builder $query): Builder
    {
        return $query->where('is_hidden_by_admin', false)
            ->where('availability', '!=', ProductAvailability::TemporarilyUnavailable->value)
            ->whereHas('farmer', fn (Builder $farmerQuery) => $farmerQuery->visibleToCustomers());
    }

    // newest first - "new" matlab jab customer ko pehli baar dikha: product add hua, ya farmer baad mein
    // approve hua. GREATEST() ki jagah CASE taake MySQL aur sqlite test DB dono pe chale
    public function scopeNewestListedFirst(Builder $query): Builder
    {
        return $query->select('products.*')
            ->join('farmer_profiles', 'farmer_profiles.id', '=', 'products.farmer_profile_id')
            ->orderByRaw('CASE WHEN farmer_profiles.approved_at > products.created_at THEN farmer_profiles.approved_at ELSE products.created_at END DESC')
            ->orderByDesc('products.id');
    }

    public function canBeOrdered(): bool
    {
        return $this->availability === ProductAvailability::Available && $this->stock_quantity > 0;
    }

    public function isRunningLow(): bool
    {
        return $this->stock_quantity > 0
            && $this->stock_quantity <= config('marketlink.low_stock_threshold');
    }

    // seeded photos public/images mein, upload wali storage mein
    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return asset($this->categoryFallbackPhotoPath());
        }

        if (Str::startsWith($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    // apni photo na ho to category ki photo, taake grid mein photo aur placeholder mix na hon
    private function categoryFallbackPhotoPath(): string
    {
        $photoPathByCategorySlug = [
            'vegetables' => 'images/products/category-vegetables.webp',
            'fruits' => 'images/products/category-fruits.webp',
            'herbs' => 'images/products/basil.webp',
            'dairy-eggs' => 'images/products/eggs.webp',
            'baked-goods' => 'images/products/sourdough.webp',
        ];

        return $photoPathByCategorySlug[$this->category?->slug] ?? 'images/products/category-general.webp';
    }
}
