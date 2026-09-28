@props(['title', 'body', 'action', 'method' => 'POST', 'confirmLabel' => 'Confirm', 'confirmClass' => 'btn-danger', 'openByDefault' => false])

{{-- koi bhi button <x-slot:trigger> mein - seedha submit ki jagah ye dialog khulta hai. <x-slot:fields> mein
     extra inputs (jaise reason). Validation error pe open-by-default lagao taake error dikhe --}}
<div x-data="{ confirmOpen: @js($openByDefault) }" class="inline-block">
    <span @click="confirmOpen = true">{{ $trigger }}</span>

    {{-- <body> mein teleport - .card ka blur fixed overlay ko card ke andar hi band kar deta --}}
    <template x-teleport="body">
        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-soil/50" @click="confirmOpen = false"></div>

            <div class="modal-glass relative w-full max-w-sm p-6" @click.stop x-show="confirmOpen"
                 x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <h3 class="font-display text-lg font-semibold">{{ $title }}</h3>
                <p class="mt-2 text-sm text-soil-muted">{{ $body }}</p>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @if (strtoupper($method) !== 'POST')
                        @method($method)
                    @endif

                    @isset($fields)
                        <div class="mt-4">{{ $fields }}</div>
                    @endisset

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" class="btn-outline" @click="confirmOpen = false">Cancel</button>
                        <button type="submit" class="{{ $confirmClass }}">{{ $confirmLabel }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
