@php
    $marketsForScript = $markets->map(fn ($market) => [
        'id' => $market->id,
        'operatingDaysText' => $market->operatingDaysText(),
        'timingText' => $market->timingText(),
    ]);
@endphp

<div x-data="{
        markets: @js($marketsForScript),
        selectedMarketId: '{{ old('market_id', $pickupWindow->market_id) }}',
        get selectedMarket() { return this.markets.find((market) => market.id == this.selectedMarketId) ?? null },
    }"
     class="grid gap-6 sm:grid-cols-2">

    <div class="sm:col-span-2">
        <label class="form-label" for="market_id">Market</label>
        <select id="market_id" name="market_id" class="form-input" x-model="selectedMarketId" required>
            <option value="">Choose a market</option>
            @foreach ($markets as $market)
                <option value="{{ $market->id }}">{{ $market->name }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-soil-muted" x-show="selectedMarket" x-cloak>
            Open <span x-text="selectedMarket?.operatingDaysText"></span>, <span x-text="selectedMarket?.timingText"></span>.
        </p>
        <x-input-error :messages="$errors->get('market_id')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="day_of_week">Day</label>
        <select id="day_of_week" name="day_of_week" class="form-input" required>
            @foreach (\App\Models\PickupWindow::dayNames() as $dayNumber => $dayName)
                <option value="{{ $dayNumber }}" @selected((string) old('day_of_week', $pickupWindow->day_of_week) === (string) $dayNumber)>{{ $dayName }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('day_of_week')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="max_orders">Max orders in this slot</label>
        <input id="max_orders" type="number" min="1" name="max_orders" value="{{ old('max_orders', $pickupWindow->max_orders ?? 10) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('max_orders')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="starts_at">Starts at</label>
        <input id="starts_at" type="time" name="starts_at" value="{{ old('starts_at', $pickupWindow->starts_at) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('starts_at')" class="mt-1.5" />
    </div>

    <div>
        <label class="form-label" for="ends_at">Ends at</label>
        <input id="ends_at" type="time" name="ends_at" value="{{ old('ends_at', $pickupWindow->ends_at) }}" class="form-input" required>
        <x-input-error :messages="$errors->get('ends_at')" class="mt-1.5" />
    </div>
</div>
