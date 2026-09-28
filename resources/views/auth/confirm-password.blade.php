<x-guest-layout>
    <h1 class="font-display text-2xl font-semibold">Confirm your password</h1>
    <p class="mt-2 text-sm text-soil-muted">
        This is a secure area. Please confirm your password before continuing.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label class="form-label" for="password">Password</label>
            <input id="password" type="password" name="password" class="form-input" required autocomplete="current-password" autofocus>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <button type="submit" class="btn-glass-orange w-full">Confirm</button>
    </form>
</x-guest-layout>
