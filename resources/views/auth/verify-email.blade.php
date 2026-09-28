<x-guest-layout>
    <h1 class="font-display text-2xl font-semibold">Verify your email</h1>
    <p class="mt-3 text-sm text-soil-muted">
        Thanks for signing up! Before getting started, click the verification link we just emailed you.
        Didn't get it? We can send another one.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-leaf-300/40 bg-leaf-500/25 px-4 py-3 text-sm text-white">
            <iconify-icon icon="tabler:circle-check" class="text-lg"></iconify-icon>
            A new verification link has been sent to the email address you provided.
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-glass-orange">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-soil-muted hover:text-soil">Log out</button>
        </form>
    </div>
</x-guest-layout>
