<section class="card">
    <h2 class="font-semibold">Profile information</h2>
    <p class="mt-1 text-sm text-soil-muted">Update your name and email address.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label class="form-label" for="name">Name</label>
            <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        <div>
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 text-sm text-soil-muted">
                    Your email address is unverified.
                    <button form="send-verification" class="font-medium text-leaf-600 hover:text-leaf-800">Resend the verification email.</button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 font-medium text-leaf-600">A new verification link has been sent.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Save</button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-leaf-600">Saved.</p>
            @endif
        </div>
    </form>
</section>
