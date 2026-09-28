@php
    $selectedAvailability = old('availability', $product->availability?->value ?? \App\Enums\ProductAvailability::Available->value);
@endphp

<div x-data="{ imagePreviewUrl: @js($product->exists ? $product->imageUrl() : null) }" class="grid gap-6 sm:grid-cols-2">

    <div class="sm:col-span-2">
        <label class="form-label" for="image">Photo</label>
        <div class="flex items-center gap-4">
            <img x-show="imagePreviewUrl" :src="imagePreviewUrl" alt="Product photo preview" class="h-24 w-24 rounded-xl object-cover">
            <input id="image" type="file" name="image" accept="image/*" class="form-input"
                   @change="imagePreviewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : imagePreviewUrl">
        </div>
        <p class="mt-1 text-xs text-soil-muted">Leave empty to keep the current photo.</p>
        <x-input-error :messages="$errors->get('image')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="product_category_id">Category</label>
        <select id="product_category_id" name="product_category_id" class="form-input" required>
            <option value="">Choose a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('product_category_id', $product->product_category_id) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('product_category_id')" class="mt-1.5" />
    </div>

    <div class="sm:col-span-2">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" rows="3" class="form-input">{{ old('description', $product->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="price">Price</label>
        <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('price')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="unit">Unit</label>
        <select id="unit" name="unit" class="form-input" required>
            @foreach (config('marketlink.product_units') as $unitOption)
                <option value="{{ $unitOption }}" @selected(old('unit', $product->unit) === $unitOption)>{{ ucfirst($unitOption) }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('unit')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="stock_quantity">Stock on hand</label>
        <input id="stock_quantity" type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('stock_quantity')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="weekly_default_quantity">Usual weekly amount</label>
        <input id="weekly_default_quantity" type="number" min="0" name="weekly_default_quantity" value="{{ old('weekly_default_quantity', $product->weekly_default_quantity) }}" class="form-input" required>
        <p class="mt-1 text-xs text-soil-muted">Used by "Refill weekly stock" to top your stock back up each week.</p>
        <x-input-error :messages="$errors->get('weekly_default_quantity')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="availability">Availability</label>
        <select id="availability" name="availability" class="form-input" required>
            @foreach (\App\Enums\ProductAvailability::cases() as $availabilityOption)
                <option value="{{ $availabilityOption->value }}" @selected($selectedAvailability === $availabilityOption->value)>
                    {{ $availabilityOption->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('availability')" class="mt-1.5" />
    </div>
</div>
