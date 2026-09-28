{{--
    $chatRoute, $historyRoute, $unreadCountRoute, $markReadRoute, $label chahiye - customer / farmer / admin agent-chat files se include hota hai.
    page pe ek hi widget hota hai, panel.blade.php role dekh ke chunta hai
--}}
<div x-data="{ open: false, unreadCount: 0 }"
     x-init="fetch('{{ $unreadCountRoute }}', { headers: { Accept: 'application/json' } }).then((r) => r.json()).then((d) => unreadCount = d.count).catch(() => {});
             setInterval(() => { if (! open) fetch('{{ $unreadCountRoute }}', { headers: { Accept: 'application/json' } }).then((r) => r.json()).then((d) => unreadCount = d.count).catch(() => {}); }, 60000)"
     @open-agent-chat.window="open = true; $dispatch('agent-chat-opened')"
     data-agent-widget data-chat-url="{{ $chatRoute }}" data-history-url="{{ $historyRoute }}" data-mark-read-url="{{ $markReadRoute }}"
     class="fixed bottom-6 right-6 z-40">

    <button type="button" @click="open = ! open; if (open) { unreadCount = 0; $dispatch('agent-chat-opened'); }"
            class="icon-chip relative h-14 w-14 bg-leaf-500 text-2xl text-white shadow-lg transition hover:bg-leaf-600" aria-label="{{ $label }}">
        <iconify-icon icon="tabler:message-chatbot" x-show="! open"></iconify-icon>
        <iconify-icon icon="tabler:x" x-show="open" x-cloak></iconify-icon>
        <span x-show="unreadCount > 0" x-cloak x-text="unreadCount > 9 ? '9+' : unreadCount"
              class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-tomato-500 px-1 text-[11px] font-semibold text-white"></span>
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute bottom-[4.5rem] right-0 flex h-[32rem] w-80 flex-col overflow-hidden rounded-card border border-cream-dark bg-white shadow-xl sm:w-96">

        <div class="flex items-center justify-between gap-2 border-b border-cream-dark bg-leaf-50 px-4 py-3">
            <p class="flex items-center gap-2 font-medium text-leaf-800">
                <iconify-icon icon="tabler:sparkles"></iconify-icon>
                {{ $label }}
            </p>
            <button type="button" data-agent-voice-toggle class="text-soil-muted hover:text-soil" title="Read replies aloud">
                <iconify-icon icon="tabler:volume-off"></iconify-icon>
            </button>
        </div>

        <div class="flex-1 space-y-3 overflow-y-auto p-4" data-agent-message-list>
            <p class="text-center text-sm text-soil-muted" data-agent-empty-hint>Ask me anything about your account...</p>
        </div>

        <div data-agent-reply-quote hidden class="flex items-start gap-2 border-t border-cream-dark bg-cream px-3 py-2 text-xs text-soil-muted">
            <iconify-icon icon="tabler:corner-up-left" class="mt-0.5 shrink-0"></iconify-icon>
            <p class="flex-1 truncate" data-agent-reply-quote-text></p>
            <button type="button" data-agent-reply-quote-clear aria-label="Cancel reply" class="shrink-0 text-soil-muted hover:text-soil">
                <iconify-icon icon="tabler:x"></iconify-icon>
            </button>
        </div>

        <form data-agent-form class="flex items-center gap-2 border-t border-cream-dark p-3">
            <button type="button" data-agent-mic-button class="icon-chip h-9 w-9 shrink-0 bg-cream text-soil-muted hover:text-soil" title="Speak instead of typing">
                <iconify-icon icon="tabler:microphone"></iconify-icon>
            </button>
            <input type="text" name="message" placeholder="Type a message..." class="form-input flex-1 py-2" data-agent-input autocomplete="off">
            <button type="submit" class="btn-primary h-9 w-9 shrink-0 !px-0" aria-label="Send">
                <iconify-icon icon="tabler:send-2"></iconify-icon>
            </button>
        </form>
    </div>
</div>
