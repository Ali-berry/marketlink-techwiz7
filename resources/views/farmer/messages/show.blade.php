<x-layouts.panel :title="$conversation->customer->name">

    <div class="mb-4">
        <a href="{{ route('farmer.messages.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to messages
        </a>
    </div>

    @include('messages._thread', [
        'conversation' => $conversation,
        'otherPartyName' => $conversation->customer->name,
        'otherPartyAvatarUrl' => null,
        'sendAction' => route('farmer.messages.reply', $conversation),
        'orderRouteName' => 'farmer.orders.show',
    ])

    @push('scripts')
        @vite('resources/js/chat-composer.js')
    @endpush

</x-layouts.panel>
