<section class="card border-red-100">
    <h2 class="font-semibold text-red-800">Delete account</h2>
    <p class="mt-1 text-sm text-soil-muted">
        Once your account is deleted, all of its data is permanently gone. Download anything you want to keep first.
    </p>

    <div class="mt-4">
        <x-ui.confirm-dialog title="Delete your account?"
                              body="This can't be undone. Enter your password to confirm."
                              :action="route('profile.destroy')" method="DELETE" confirm-label="Delete account"
                              :open-by-default="$errors->userDeletion->isNotEmpty()">
            <x-slot:trigger>
                <span class="btn-danger cursor-pointer">Delete account</span>
            </x-slot:trigger>
            <x-slot:fields>
                <label class="form-label" for="delete_password">Password</label>
                <input id="delete_password" name="password" type="password" class="form-input" placeholder="Password">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5" />
            </x-slot:fields>
        </x-ui.confirm-dialog>
    </div>
</section>
