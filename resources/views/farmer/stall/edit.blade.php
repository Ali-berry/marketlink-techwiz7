@php
    $startLatitude = old('latitude', $farmer->latitude ?? config('marketlink.default_map_center.latitude'));
    $startLongitude = old('longitude', $farmer->longitude ?? config('marketlink.default_map_center.longitude'));
    $farmerMarketIds = $farmer->markets->pluck('id')->all();
@endphp

<x-layouts.panel title="Stall profile">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Stall profile</h2>
        <p class="mt-1 text-sm text-soil-muted">This is what customers see about your stall.</p>
    </div>

    <form method="POST" action="{{ route('farmer.stall.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="card" x-data="{ coverPreviewUrl: @js($farmer->coverImageUrl()) }">
            <h3 class="font-semibold">Basics</h3>

            <div class="mt-4 grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="form-label" for="cover_image">Cover photo</label>
                    <div class="flex items-center gap-4">
                        <img :src="coverPreviewUrl" alt="Stall cover preview" class="h-24 w-40 rounded-xl object-cover object-[center_20%]">
                        <input id="cover_image" type="file" name="cover_image" accept="image/*" class="form-input"
                               @change="coverPreviewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : coverPreviewUrl">
                    </div>
                    <x-input-error :messages="$errors->get('cover_image')" class="mt-1.5" />
                </div>

                <div>
                    <label class="form-label" for="stall_name">Stall name</label>
                    <input id="stall_name" type="text" name="stall_name" value="{{ old('stall_name', $farmer->stall_name) }}" class="form-input" required>
                    <x-input-error :messages="$errors->get('stall_name')" class="mt-1.5" />
                </div>

                <div>
                    <label class="form-label" for="contact_person">Contact person</label>
                    <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $farmer->contact_person) }}" class="form-input" required>
                    <x-input-error :messages="$errors->get('contact_person')" class="mt-1.5" />
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label" for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="3" class="form-input">{{ old('bio', $farmer->bio) }}</textarea>
                    <x-input-error :messages="$errors->get('bio')" class="mt-1.5" />
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label" for="farming_experience">Farming experience (optional)</label>
                    <input id="farming_experience" type="text" name="farming_experience"
                           value="{{ old('farming_experience', $farmer->farming_experience) }}" class="form-input"
                           placeholder="e.g. 5 years growing organic vegetables">
                    <p class="mt-1 text-xs text-soil-muted">Shown on your public profile - separate from how long you've been on MarketLink.</p>
                    <x-input-error :messages="$errors->get('farming_experience')" class="mt-1.5" />
                </div>

                <div>
                    <label class="form-label" for="order_cutoff_hours">Order cutoff (hours before pickup)</label>
                    <input id="order_cutoff_hours" type="number" min="1" max="168" name="order_cutoff_hours"
                           value="{{ old('order_cutoff_hours', $farmer->order_cutoff_hours) }}" class="form-input" required>
                    <p class="mt-1 text-xs text-soil-muted">Customers can't change or cancel an order once it's this close to pickup time.</p>
                    <x-input-error :messages="$errors->get('order_cutoff_hours')" class="mt-1.5" />
                </div>
            </div>
        </div>

        <div class="card">
            <h3 class="font-semibold">Location</h3>
            <p class="mt-1 text-sm text-soil-muted">Drag the pin (or tap the map) to set exactly where your stall or farm is.</p>

            <div class="mt-4 grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="form-label" for="address">Address</label>
                    <input id="address" type="text" name="address" value="{{ old('address', $farmer->address) }}" class="form-input" required>
                    <x-input-error :messages="$errors->get('address')" class="mt-1.5" />
                </div>

                <div class="sm:col-span-2">
                    <div class="h-80 overflow-hidden rounded-card border border-cream-dark"
                         data-stall-location-map
                         data-latitude="{{ $startLatitude }}"
                         data-longitude="{{ $startLongitude }}"
                         data-zoom="13"
                         data-latitude-input="#latitude-input"
                         data-longitude-input="#longitude-input"
                         role="img" aria-label="Map to set your stall's location"></div>
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
        </div>

        <div class="card">
            <h3 class="font-semibold">Markets you sell at</h3>
            <p class="mt-1 text-sm text-soil-muted">Tick each market and add your stall number so customers can find you.</p>

            <div class="mt-4 space-y-3">
                @foreach ($markets as $market)
                    <div x-data="{ marketChecked: {{ in_array($market->id, old('markets', $farmerMarketIds)) ? 'true' : 'false' }} }"
                         class="rounded-xl border border-cream-dark p-4">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="markets[]" value="{{ $market->id }}" x-model="marketChecked" class="rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
                            <span class="font-medium">{{ $market->name }}</span>
                            <span class="text-sm text-soil-muted">{{ $market->operatingDaysText() }}</span>
                        </label>

                        <div x-show="marketChecked" x-cloak class="mt-3 max-w-xs">
                            <label class="form-label" for="stall_number_{{ $market->id }}">Stall number</label>
                            <input id="stall_number_{{ $market->id }}" type="text" name="stall_numbers[{{ $market->id }}]"
                                   value="{{ old('stall_numbers.'.$market->id, $farmer->markets->firstWhere('id', $market->id)?->pivot->stall_number) }}"
                                   class="form-input" placeholder="e.g. A-12">
                        </div>
                    </div>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('markets')" class="mt-1.5" />
        </div>

        {{-- har switch upar wale ke on hone pe hi enabled, koi off karo to neeche wale sab off --}}
        <div class="card" id="urgent-orders"
             x-data="{
                 acceptsUrgentOrders: @js((bool) old('accepts_urgent_orders', $farmer->accepts_urgent_orders)),
                 aiAutoConfirmsUrgent: @js((bool) old('ai_auto_confirms_urgent', $farmer->ai_auto_confirms_urgent)),
                 aiMarksUrgentReady: @js((bool) old('ai_marks_urgent_ready', $farmer->ai_marks_urgent_ready)),
             }">
            <div class="flex items-start gap-3">
                <span class="icon-chip h-10 w-10 shrink-0 bg-tomato-50 text-tomato-600">
                    <iconify-icon icon="tabler:clock-bolt"></iconify-icon>
                </span>
                <div>
                    <h3 class="font-semibold">Urgent orders</h3>
                    <p class="mt-1 text-sm text-soil-muted">Let customers order for pickup within the hour, collected at your stall or farm address above - not at a market.</p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-cream-dark p-4">
                    <span>
                        <span class="block font-medium">Accept urgent orders</span>
                        <span class="block text-sm text-soil-muted">Customers nearby can order for pickup in 15 to 120 minutes.</span>
                    </span>
                    <input type="hidden" name="accepts_urgent_orders" value="0">
                    <input type="checkbox" name="accepts_urgent_orders" value="1" x-model="acceptsUrgentOrders"
                           @change="if (! acceptsUrgentOrders) { aiAutoConfirmsUrgent = false; aiMarksUrgentReady = false }"
                           class="mt-1 h-5 w-5 rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
                </label>

                <label class="flex items-start justify-between gap-4 rounded-xl border border-cream-dark p-4"
                       :class="acceptsUrgentOrders ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'">
                    <span>
                        <span class="block font-medium">Let MarketLink AI auto-confirm urgent orders</span>
                        <span class="block text-sm text-soil-muted">When this is on, MarketLink AI confirms urgent orders for you as long as you have stock - you'll get a notification for every one.</span>
                    </span>
                    <input type="hidden" name="ai_auto_confirms_urgent" value="0">
                    <input type="checkbox" name="ai_auto_confirms_urgent" value="1" x-model="aiAutoConfirmsUrgent"
                           :disabled="! acceptsUrgentOrders"
                           @change="if (! aiAutoConfirmsUrgent) aiMarksUrgentReady = false"
                           class="mt-1 h-5 w-5 rounded border-cream-dark text-leaf-500 focus:ring-leaf-400 disabled:opacity-50">
                </label>

                <div class="rounded-xl border border-cream-dark p-4"
                     :class="aiAutoConfirmsUrgent ? '' : 'opacity-60'">
                    <label class="flex items-start justify-between gap-4"
                           :class="aiAutoConfirmsUrgent ? 'cursor-pointer' : 'cursor-not-allowed'">
                        <span>
                            <span class="block font-medium">Let MarketLink AI mark urgent orders ready</span>
                            <span class="block text-sm text-soil-muted">Turn this on only if you keep urgent items packed and ready. MarketLink AI will mark the order ready after your prep time and tell the customer to come.</span>
                        </span>
                        <input type="hidden" name="ai_marks_urgent_ready" value="0">
                        <input type="checkbox" name="ai_marks_urgent_ready" value="1" x-model="aiMarksUrgentReady"
                               :disabled="! aiAutoConfirmsUrgent"
                               class="mt-1 h-5 w-5 rounded border-cream-dark text-leaf-500 focus:ring-leaf-400 disabled:opacity-50">
                    </label>

                    <div class="mt-4 max-w-xs">
                        <label class="form-label" for="urgent_prep_minutes">Prep time (minutes)</label>
                        <input id="urgent_prep_minutes" type="number" min="0" max="60" name="urgent_prep_minutes" class="form-input"
                               value="{{ old('urgent_prep_minutes', $farmer->urgent_prep_minutes) }}"
                               :disabled="! aiMarksUrgentReady" aria-describedby="urgent_prep_minutes_hint">
                        <p id="urgent_prep_minutes_hint" class="mt-1.5 text-xs text-soil-muted">Counted from when the order is confirmed. Use 0 if it's ready the moment it's confirmed.</p>
                        <x-input-error :messages="$errors->get('urgent_prep_minutes')" class="mt-1.5" />
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <label class="form-label" for="urgent_pickup_starts_at">Urgent pickups from</label>
                        <input id="urgent_pickup_starts_at" type="time" name="urgent_pickup_starts_at" class="form-input"
                               value="{{ old('urgent_pickup_starts_at', $farmer->urgent_pickup_starts_at ? \Carbon\Carbon::parse($farmer->urgent_pickup_starts_at)->format('H:i') : '09:00') }}">
                        <x-input-error :messages="$errors->get('urgent_pickup_starts_at')" class="mt-1.5" />
                    </div>
                    <div>
                        <label class="form-label" for="urgent_pickup_ends_at">Until</label>
                        <input id="urgent_pickup_ends_at" type="time" name="urgent_pickup_ends_at" class="form-input"
                               value="{{ old('urgent_pickup_ends_at', $farmer->urgent_pickup_ends_at ? \Carbon\Carbon::parse($farmer->urgent_pickup_ends_at)->format('H:i') : '18:00') }}">
                        <x-input-error :messages="$errors->get('urgent_pickup_ends_at')" class="mt-1.5" />
                    </div>
                    <div>
                        <label class="form-label" for="max_urgent_orders_per_hour">Most urgent orders per hour</label>
                        <input id="max_urgent_orders_per_hour" type="number" min="1" max="30" name="max_urgent_orders_per_hour" class="form-input"
                               value="{{ old('max_urgent_orders_per_hour', $farmer->max_urgent_orders_per_hour) }}" required>
                        <x-input-error :messages="$errors->get('max_urgent_orders_per_hour')" class="mt-1.5" />
                    </div>
                </div>
                <p class="text-xs text-soil-muted">Times are in your first market's timezone. Once you hit the hourly limit, you stop showing up for urgent orders until the hour has passed.</p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                <iconify-icon icon="tabler:check"></iconify-icon>
                Save stall profile
            </button>
        </div>
    </form>

    @push('scripts')
        @vite('resources/js/stall-location-map.js')
    @endpush

</x-layouts.panel>
