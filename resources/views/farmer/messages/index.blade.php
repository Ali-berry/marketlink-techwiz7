<x-layouts.panel title="Messages">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Messages</h2>
        <p class="mt-1 text-sm text-soil-muted">Conversations customers have started with your stall.</p>
    </div>

    @if ($conversations->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:messages" title="No conversations yet"
                               message="When a customer messages your stall, it'll show up here." />
        </div>
    @else
        <div class="space-y-3">
            @foreach ($conversations as $conversation)
                <a href="{{ route('farmer.messages.show', $conversation) }}" class="card flex items-center gap-3 p-4 hover:border-leaf-200">
                    <span class="icon-chip h-12 w-12 shrink-0 bg-cream text-sm font-semibold text-soil">
                        {{ strtoupper(substr($conversation->customer->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate font-medium">{{ $conversation->customer->name }}</p>
                            @if ($conversation->latestMessage)
                                <span class="shrink-0 text-xs text-soil-muted">{{ $conversation->latestMessage->displayTime() }}</span>
                            @endif
                        </div>
                        <p class="truncate text-sm text-soil-muted">{{ $conversation->latestMessage?->previewText() }}</p>
                    </div>
                    @if ($conversation->unread_messages_count > 0)
                        <span class="shrink-0 rounded-full bg-tomato-500 px-2 py-0.5 text-xs font-semibold text-white">
                            {{ $conversation->unread_messages_count }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

</x-layouts.panel>
