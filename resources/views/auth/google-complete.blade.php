<x-guest-layout>
    <h1 class="font-display text-3xl font-semibold tracking-tight">Almost there, {{ \Illuminate\Support\Str::of($googleSignup['name'])->before(' ') }}</h1>
    <p class="mt-2 text-sm text-soil-muted">
        Signed in as {{ $googleSignup['email'] }}. One more thing Google can't tell us - are you here to buy or to sell?
    </p>

    <div x-data="{ accountType: '{{ old('account_type', 'customer') }}' }" class="mt-6">

        <div class="grid grid-cols-2 gap-3">
            <button type="button" @click="accountType = 'customer'"
                    :class="{ 'is-selected': accountType === 'customer' }" :aria-pressed="(accountType === 'customer').toString()"
                    class="glass-pill">
                <iconify-icon icon="tabler:basket" class="text-lg"></iconify-icon>
                I want to buy
            </button>

            <button type="button" @click="accountType = 'farmer'"
                    :class="{ 'is-selected': accountType === 'farmer' }" :aria-pressed="(accountType === 'farmer').toString()"
                    class="glass-pill">
                <iconify-icon icon="tabler:tractor" class="text-lg"></iconify-icon>
                I want to sell
            </button>
        </div>

        <form method="POST" action="{{ route('auth.google.complete.store') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="account_type" :value="accountType">

            <div x-show="accountType === 'customer'" x-cloak class="space-y-4">
                <div>
                    <label class="form-label" for="area">Area / Location (optional)</label>
                    @include('partials.texas-area-combobox', [
                        'inputId' => 'area',
                        'inputName' => 'area',
                        'latInputName' => 'latitude',
                        'lngInputName' => 'longitude',
                        'placeholder' => 'City or area...',
                    ])
                    <x-input-error :messages="$errors->get('area')" class="mt-1.5" />
                </div>

                <div>
                    <label class="form-label" for="address">Address</label>
                    <textarea id="address" name="address" rows="2" class="form-input" autocomplete="street-address">{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1.5" />
                </div>
            </div>

            <div x-show="accountType === 'farmer'" x-cloak class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="stall_name">Stall / business name</label>
                        <input id="stall_name" type="text" name="stall_name" value="{{ old('stall_name') }}" class="form-input">
                        <x-input-error :messages="$errors->get('stall_name')" class="mt-1.5" />
                    </div>
                    <div>
                        <label class="form-label" for="contact_person">Contact person</label>
                        <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $googleSignup['name']) }}" class="form-input" autocomplete="name">
                        <x-input-error :messages="$errors->get('contact_person')" class="mt-1.5" />
                    </div>
                </div>

                <div>
                    <label class="form-label" for="market_id">Select market</label>
                    <select id="market_id" name="market_id" class="form-input">
                        <option value="">Choose the market you'll sell at</option>
                        @foreach ($markets as $market)
                            <option value="{{ $market->id }}" @selected(old('market_id') == $market->id)>{{ $market->name }} - {{ $market->city }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-soil-muted">You can sell at more markets later from your stall settings.</p>
                    <x-input-error :messages="$errors->get('market_id')" class="mt-1.5" />
                </div>
            </div>

            <div>
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="form-input" autocomplete="tel">
                <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
            </div>

            <button type="submit" class="btn-glass-orange w-full">Finish setting up my account</button>
        </form>
    </div>
</x-guest-layout>
