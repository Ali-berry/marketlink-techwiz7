<x-guest-layout>
    <h1 class="font-display text-2xl font-semibold">Forgot your password?</h1>
    <p class="mt-2 text-sm text-soil-muted">
        No problem. Tell us your email and we'll send a link to choose a new one.
    </p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <button type="submit" class="btn-glass-orange w-full">Email password reset link</button>
    </form>
</x-guest-layout>
