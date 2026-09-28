@php
    $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $selectedDays = old('operating_days', $market->operating_days ?? []);
    $startLatitude = old('latitude', $market->latitude ?? config('marketlink.default_map_center.latitude'));
    $startLongitude = old('longitude', $market->longitude ?? config('marketlink.default_map_center.longitude'));
@endphp

<div x-data="{ coverPreviewUrl: @js($market->exists ? $market->coverImageUrl() : null) }" class="grid gap-6 sm:grid-cols-2">

    <div class="sm:col-span-2">
        <label class="form-label" for="cover_image">Cover photo</label>
        <div class="flex items-center gap-4">
            <img x-show="coverPreviewUrl" :src="coverPreviewUrl" alt="Market cover preview" class="h-24 w-40 rounded-xl object-cover">
            <input id="cover_image" type="file" name="cover_image" accept="image/*" class="form-input"
                   @change="coverPreviewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : coverPreviewUrl">
        </div>
        <x-input-error :messages="$errors->get('cover_image')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="name">Market name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $market->name) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="city">City</label>
        <input id="city" type="text" name="city" value="{{ old('city', $market->city) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('city')" class="mt-1.5" />
    </div>

    <div class="sm:col-span-2">
        <label class="form-label" for="address">Address</label>
        <input id="address" type="text" name="address" value="{{ old('address', $market->address) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('address')" class="mt-1.5" />
    </div>

    <div class="sm:col-span-2">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" rows="2" class="form-input">{{ old('description', $market->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1.5" />
    </div>

    <div class="sm:col-span-2">
        <label class="form-label">Operating days</label>
        <div class="flex flex-wrap gap-3">
            @foreach ($dayNames as $dayOption)
                <label class="flex items-center gap-2 rounded-xl border border-cream-dark px-3 py-2 text-sm has-[:checked]:border-leaf-400 has-[:checked]:bg-leaf-50">
                    <input type="checkbox" name="operating_days[]" value="{{ $dayOption }}"
                           @checked(in_array($dayOption, $selectedDays)) class="rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
                    {{ ucfirst($dayOption) }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('operating_days')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="opens_at">Opens at</label>
        <input id="opens_at" type="time" name="opens_at" value="{{ substr(old('opens_at', $market->opens_at) ?? '', 0, 5) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('opens_at')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="closes_at">Closes at</label>
        <input id="closes_at" type="time" name="closes_at" value="{{ substr(old('closes_at', $market->closes_at) ?? '', 0, 5) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('closes_at')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="timezone">Time zone</label>
        <select id="timezone" name="timezone" class="form-input" required>
            @foreach (\App\Models\Market::TIMEZONES as $timezoneValue => $timezoneDetails)
                <option value="{{ $timezoneValue }}" @selected(old('timezone', $market->timezone ?? 'America/Chicago') === $timezoneValue)>{{ $timezoneDetails['name'] }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-soil-muted">El Paso is on Mountain Time, the rest of Texas is Central.</p>
        <x-input-error :messages="$errors->get('timezone')" class="mt-1.5" />
    </div>

    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $market->is_active ?? true)) class="rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
            Active (visible to customers)
        </label>
    </div>

    <div class="sm:col-span-2">
        <label class="form-label">Location</label>
        <p class="mb-2 text-xs text-soil-muted">Drag the pin (or tap the map) to set the exact spot.</p>
        <div class="h-80 overflow-hidden rounded-card border border-cream-dark"
             data-stall-location-map
             data-latitude="{{ $startLatitude }}"
             data-longitude="{{ $startLongitude }}"
             data-zoom="13"
             data-latitude-input="#latitude-input"
             data-longitude-input="#longitude-input"
             role="img" aria-label="Map to set this market's location"></div>
    </div>

    <div>
        <label class="form-label" for="latitude-input">Latitude</label>
        <input id="latitude-input" type="text" name="latitude" value="{{ $startLatitude }}" class="form-input" readonly required>
        <x-input-error :messages="$errors->get('latitude')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="longitude-input">Longitude</label>
        <input id="longitude-input" type="text" name="longitude" value="{{ $startLongitude }}" class="form-input" readonly required>
        <x-input-error :messages="$errors->get('longitude')" class="mt-1.5" />
    </div>
</div>
