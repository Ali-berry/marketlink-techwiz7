<?php

namespace App\Http\Controllers\Farmer;

use App\Enums\ProductAvailability;
use App\Enums\UserRole;
use App\Helpers\MoneyFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreProductRequest;
use App\Http\Requests\Farmer\UpdateProductAvailabilityRequest;
use App\Http\Requests\Farmer\UpdateProductRequest;
use App\Models\FarmerProfile;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Agent\AgentProactiveMessenger;
use App\Services\ProductStockUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly AgentProactiveMessenger $proactiveMessenger)
    {
    }

    public function index(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $products = $farmer->products()
            ->with('category')
            ->when($request->filled('category_id'), fn ($query) => $query->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('availability'), fn ($query) => $query->where('availability', $request->string('availability')))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $categories = ProductCategory::orderBy('sort_order')->get();

        return view('farmer.products.index', [
            'farmer' => $farmer,
            'products' => $products,
            'categories' => $categories,
            'availabilityOptions' => ProductAvailability::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_if(! $request->user()->farmerProfile, 403, 'Your farmer profile is missing. Please contact the admin.');

        return view('farmer.products.create', [
            'product' => new Product(['availability' => ProductAvailability::Available]),
            'categories' => ProductCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $farmer = $request->user()->farmerProfile;
        $validated = $request->validated();

        $product = $farmer->products()->create([
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['name'], $farmer),
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null,
        ]);

        $this->notifyAdminsOfNewProduct($farmer, $product);

        return redirect()->route('farmer.products.edit', $product)->with('success', $product->name.' was added to your stall.');
    }

    // moderate-content wale sab admins ko naya product dikha do
    private function notifyAdminsOfNewProduct(FarmerProfile $farmer, Product $product): void
    {
        foreach (User::adminsWithPermission('moderate-content') as $admin) {
            $this->proactiveMessenger->send(
                recipient: $admin,
                userType: UserRole::Admin,
                content: "{$farmer->stall_name} added a new product: {$product->name}, "
                    .MoneyFormatter::format($product->price)."/{$product->unit}. Looks okay, or should I hide it?",
                contextType: 'product',
                contextId: $product->id,
                contextLabel: $product->name,
                actions: ['Looks okay', 'Hide it', 'Details'],
            );
        }
    }

    public function edit(Request $request, Product $product): View
    {
        $this->abortUnlessOwnedByFarmer($request, $product);

        return view('farmer.products.edit', [
            'product' => $product,
            'categories' => ProductCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductStockUpdater $stockUpdater): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteUploadedImage($product);
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        // jaise stock 0 se 10 kiya to restock alerts jate hain (ProductStockUpdater)
        $stockUpdater->update($product, $validated);

        return redirect()->route('farmer.products.edit', $product)->with('success', $product->name.' was updated.');
    }

    public function updateAvailability(UpdateProductAvailabilityRequest $request, Product $product, ProductStockUpdater $stockUpdater): RedirectResponse
    {
        $newAvailability = ProductAvailability::from($request->validated()['availability']);

        // alert sirf tab jab dobara order ho sake - available magar 0 stock count nahi
        $stockUpdater->update($product, ['availability' => $newAvailability]);

        return back()->with('success', $product->name.' is now marked "'.$newAvailability->label().'".');
    }

    public function refillStock(Request $request, ProductStockUpdater $stockUpdater): RedirectResponse
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $refilledCount = 0;

        foreach ($farmer->products as $product) {
            $stockUpdater->refillToWeeklyAmount($product);
            $refilledCount++;
        }

        return back()->with('success', "Stock refilled for {$refilledCount} products.");
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->abortUnlessOwnedByFarmer($request, $product);

        $product->delete();

        return redirect()->route('farmer.products.index')->with('success', $product->name.' was removed from your stall.');
    }

    private function abortUnlessOwnedByFarmer(Request $request, Product $product): void
    {
        abort_unless($product->farmer_profile_id === $request->user()->farmerProfile?->id, 403);
    }

    private function generateUniqueSlug(string $name, FarmerProfile $farmer): string
    {
        $baseSlug = Str::slug($name.' '.$farmer->stall_name);
        $slug = $baseSlug;
        $suffix = 1;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.++$suffix;
        }

        return $slug;
    }

    // sirf apni upload ki hui photos delete karte hain, seeded photos (images/products/...) shared hain
    private function deleteUploadedImage(Product $product): void
    {
        if ($product->image_path && ! Str::startsWith($product->image_path, 'images/')) {
            Storage::disk('public')->delete($product->image_path);
        }
    }
}
