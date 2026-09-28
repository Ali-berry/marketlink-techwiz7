<x-guest-layout>
    <h1 class="font-display text-3xl font-semibold tracking-tight">Join MarketLink</h1>
    <p class="mt-2 text-sm text-soil-muted">Tell us what brings you here - it only takes a minute.</p>

    {{-- ?account_type=farmer (footer ka "Register as a farmer") seedha "I want to sell" pe khulta hai --}}
    @php $startingAccountType = old('account_type', request('account_type') === 'farmer' ? 'farmer' : 'customer'); @endphp
    <div x-data="{ accountType: '{{ $startingAccountType }}', password: '' }" class="mt-6">

        {{-- "buy" ya "sell" sab se pehle, chhota rakha kyunki bas switch hai --}}
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

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="account_type" :value="accountType">

            <div x-show="accountType === 'customer'" x-cloak class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="name">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-input" autocomplete="name">
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                    </div>

                    <div>
                        <label class="form-label" for="area">Area / Location</label>
                        {{-- nearest-market sorting ke liye general area, neeche wale address se alag. Abhi Texas ki static list (TexasAreas),
                             Google Places key aaye to partials.google-places-autocomplete laga do --}}
                        @include('partials.texas-area-combobox', [
                            'inputId' => 'area',
                            'inputName' => 'area',
                            'latInputName' => 'latitude',
                            'lngInputName' => 'longitude',
                            'placeholder' => 'City or area...',
                        ])
                        <x-input-error :messages="$errors->get('area')" class="mt-1.5" />
                    </div>
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
                        <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-input" autocomplete="name">
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" autocomplete="username">
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <div>
                    <label class="form-label" for="phone">Phone</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}" class="form-input" autocomplete="tel">
                    <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password" name="password" x-model="password" class="form-input" autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                </div>

                <div>
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                </div>
            </div>
            <x-ui.password-checklist />

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('login') }}" class="text-sm font-medium text-tomato-300 hover:text-tomato-200">Already registered?</a>
                <button type="submit" class="btn-glass-orange">Create account</button>
            </div>
        </form>

        @if (config('services.google.client_id'))
            <div class="mt-6 flex items-center gap-3 text-xs text-soil-muted">
                <span class="h-px flex-1 bg-white/15"></span>
                or
                <span class="h-px flex-1 bg-white/15"></span>
            </div>
            <x-ui.google-login-button class="mt-6" label="Continue with Google" />
        @endif
    </div>
</x-guest-layout>
