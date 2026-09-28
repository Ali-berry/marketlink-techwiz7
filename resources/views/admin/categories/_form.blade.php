@php $iconValue = old('icon', $category->icon ?? 'tabler:basket'); @endphp

<div x-data="{ icon: @js($iconValue) }" class="grid gap-6 sm:grid-cols-2">
    <div>
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $category->name) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="sort_order">Sort order</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" class="form-input" required>
        <p class="mt-1 text-xs text-soil-muted">Lower numbers show first.</p>
        <x-input-error :messages="$errors->get('sort_order')" class="mt-1.5" />
    </div>

    <div class="sm:col-span-2">
        <label class="form-label" for="icon">Icon name</label>
        <div class="flex items-center gap-3">
            <span class="icon-chip h-11 w-11 bg-leaf-50 text-xl text-leaf-600">
                <iconify-icon :icon="icon"></iconify-icon>
            </span>
            <input id="icon" type="text" name="icon" x-model="icon" class="form-input" placeholder="e.g. tabler:carrot" required>
        </div>
        <p class="mt-1 text-xs text-soil-muted">
            Any <a href="https://icon-sets.iconify.design/tabler/" target="_blank" rel="noopener" class="underline">Tabler icon</a> name, e.g. tabler:carrot.
        </p>
        <x-input-error :messages="$errors->get('icon')" class="mt-1.5" />
    </div>
</div>
