<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::withCount('products')->orderBy('sort_order')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new ProductCategory(['sort_order' => (ProductCategory::max('sort_order') ?? 0) + 1]),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ProductCategory::create([
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['name']),
        ]);

        return redirect()->route('admin.categories.index')->with('success', $validated['name'].' was added.');
    }

    public function edit(ProductCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')->with('success', $category->name.' was updated.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', $category->name." still has products and can't be deleted.");
        }

        $category->delete();

        return back()->with('success', $category->name.' was deleted.');
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 1;

        while (ProductCategory::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.++$suffix;
        }

        return $slug;
    }
}
