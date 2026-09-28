<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMarketRequest;
use App\Http\Requests\Admin\UpdateMarketRequest;
use App\Models\Market;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketController extends Controller
{
    public function index(Request $request): View
    {
        $markets = Market::withCount('orders')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.markets.index', compact('markets'));
    }

    public function create(): View
    {
        return view('admin.markets.create', ['market' => new Market(['is_active' => true])]);
    }

    public function store(StoreMarketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Market::create([
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['name']),
            'cover_image_path' => $request->hasFile('cover_image') ? $request->file('cover_image')->store('markets', 'public') : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.markets.index')->with('success', $validated['name'].' was created.');
    }

    public function edit(Market $market): View
    {
        return view('admin.markets.edit', compact('market'));
    }

    public function update(UpdateMarketRequest $request, Market $market): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('cover_image')) {
            $this->deleteUploadedCoverImage($market);
            $validated['cover_image_path'] = $request->file('cover_image')->store('markets', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        $market->update($validated);

        return redirect()->route('admin.markets.index')->with('success', $market->name.' was updated.');
    }

    public function destroy(Market $market): RedirectResponse
    {
        if ($market->orders()->exists()) {
            return back()->with('error', $market->name." has orders and can't be deleted. Deactivate it instead.");
        }

        $this->deleteUploadedCoverImage($market);
        $market->delete();

        return redirect()->route('admin.markets.index')->with('success', $market->name.' was deleted.');
    }

    public function toggleActive(Market $market): RedirectResponse
    {
        $market->update(['is_active' => ! $market->is_active]);

        return back()->with('success', $market->name.' is now '.($market->is_active ? 'active' : 'inactive').'.');
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while (Market::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.++$suffix;
        }

        return $slug;
    }

    private function deleteUploadedCoverImage(Market $market): void
    {
        if ($market->cover_image_path && ! Str::startsWith($market->cover_image_path, 'images/')) {
            Storage::disk('public')->delete($market->cover_image_path);
        }
    }
}
