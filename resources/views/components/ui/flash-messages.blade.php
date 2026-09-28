@if (session('success'))
    <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 5000)"
         class="mb-6 flex items-center gap-3 rounded-2xl border border-leaf-100 bg-leaf-50 px-4 py-3 text-sm text-leaf-800">
        <iconify-icon icon="tabler:circle-check" class="text-lg"></iconify-icon>
        <span class="flex-1">{{ session('success') }}</span>
        <button type="button" @click="visible = false" aria-label="Close">
            <iconify-icon icon="tabler:x"></iconify-icon>
        </button>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
        <iconify-icon icon="tabler:alert-circle" class="text-lg"></iconify-icon>
        <span>{{ session('error') }}</span>
    </div>
@endif
