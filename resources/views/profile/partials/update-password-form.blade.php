<section class="card" x-data="{ password: '' }">
    <h2 class="font-semibold">Update password</h2>
    <p class="mt-1 text-sm text-soil-muted">Use a long, random password to keep your account secure.</p>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div>
            <label class="form-label" for="update_password_current_password">Current password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-input" autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5" />
        </div>

        <div>
            <label class="form-label" for="update_password_password">New password</label>
            <input id="update_password_password" name="password" type="password" x-model="password" class="form-input" autocomplete="new-password">
            <x-ui.password-checklist />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5" />
        </div>

        <div>
            <label class="form-label" for="update_password_password_confirmation">Confirm new password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Save</button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-leaf-600">Saved.</p>
            @endif
        </div>
    </form>
</section>
