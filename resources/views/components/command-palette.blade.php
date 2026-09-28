{{--
    Ctrl+K / Cmd+K search - seedha product, farmer ya market page pe. Guest ke liye bhi (SearchController).
    Alpine sirf kholna / band karna sambhalta hai, search aur arrow keys resources/js/command-palette.js mein,
    jo har baar "command-palette-opened" event pe reset hota hai
--}}
<div x-data="{ open: false }"
     x-on:keydown.window.cmd.k.prevent="open = true; $nextTick(() => $dispatch('command-palette-opened'))"
     x-on:keydown.window.ctrl.k.prevent="open = true; $nextTick(() => $dispatch('command-palette-opened'))"
     x-on:keydown.escape.window="open = false"
     data-command-palette
     x-show="open" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 flex items-start justify-center bg-soil/50 px-4 pt-[12vh] backdrop-blur-sm">

    <div @click.outside="open = false" x-transition
         class="w-full max-w-xl overflow-hidden rounded-card border border-cream-dark bg-white shadow-2xl">

        <div class="flex items-center gap-3 border-b border-cream-dark px-5 py-4">
            <iconify-icon icon="tabler:search" class="text-xl text-soil-muted"></iconify-icon>
            <input type="text" data-command-palette-input
                   placeholder="Search products, farmers, markets..."
                   class="flex-1 border-0 bg-transparent p-0 text-base text-soil placeholder:text-soil-muted/60 focus:outline-none focus:ring-0"
                   autocomplete="off">
            <kbd class="rounded-md border border-cream-dark bg-cream px-1.5 py-0.5 text-xs text-soil-muted">Esc</kbd>
        </div>

        <div data-command-palette-results class="max-h-96 overflow-y-auto p-2">
            <p class="px-3 py-6 text-center text-sm text-soil-muted">Start typing to search across MarketLink.</p>
        </div>
    </div>
</div>
