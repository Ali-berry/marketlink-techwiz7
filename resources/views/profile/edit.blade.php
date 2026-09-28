<x-layouts.panel title="My profile">

    <div class="max-w-2xl space-y-6">
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
        @include('profile.partials.delete-user-form')
    </div>

</x-layouts.panel>
