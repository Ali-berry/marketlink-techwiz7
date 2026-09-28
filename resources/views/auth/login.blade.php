<x-guest-layout>
    <h1 class="font-display text-3xl font-semibold tracking-tight">Welcome back</h1>
    <p class="mt-2 text-sm text-soil-muted">Log in to your MarketLink account to pick up where you left off.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <label class="form-label" for="password">Password</label>
            <input id="password" type="password" name="password" class="form-input" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center gap-2 text-sm text-soil-muted">
                <input id="remember_me" type="checkbox" name="remember" class="rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
                Remember me
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-tomato-300 hover:text-tomato-200">Forgot your password?</a>
            @endif
        </div>

        <button type="submit" class="btn-glass-orange w-full">Log in</button>

        @if (config('services.google.client_id'))
            <div class="flex items-center gap-3 text-xs text-soil-muted">
                <span class="h-px flex-1 bg-white/15"></span>
                or
                <span class="h-px flex-1 bg-white/15"></span>
            </div>
            <x-ui.google-login-button />
        @endif

        <p class="text-center text-sm text-soil-muted">
            New here? <a href="{{ route('register') }}" class="font-medium text-tomato-300 hover:text-tomato-200">Create an account</a>
        </p>
    </form>

    @if (config('marketlink.show_demo_logins'))
        <div class="mt-8 border-t border-white/15 pt-6">
            <p class="text-center text-xs font-medium text-soil-muted">For judges - demo accounts</p>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <form method="POST" action="{{ route('demo-login', 'customer') }}">
                    @csrf
                    <button type="submit" class="btn-outline w-full px-2 py-2 text-xs">Enter as customer</button>
                </form>
                <form method="POST" action="{{ route('demo-login', 'farmer') }}">
                    @csrf
                    <button type="submit" class="btn-outline w-full px-2 py-2 text-xs">Enter as farmer</button>
                </form>
                <form method="POST" action="{{ route('demo-login', 'admin') }}">
                    @csrf
                    <button type="submit" class="btn-outline w-full px-2 py-2 text-xs">Enter as admin</button>
                </form>
            </div>
        </div>
    @endif
</x-guest-layout>
