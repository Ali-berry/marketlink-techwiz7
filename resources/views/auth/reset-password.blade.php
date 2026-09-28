<x-guest-layout>
    <h1 class="font-display text-2xl font-semibold">Choose a new password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4" x-data="{ password: '' }">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="form-input" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <label class="form-label" for="password">New password</label>
            <input id="password" type="password" name="password" x-model="password" class="form-input" required autocomplete="new-password">
            <x-ui.password-checklist />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div>
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <button type="submit" class="btn-glass-orange w-full">Reset password</button>
    </form>
</x-guest-layout>
