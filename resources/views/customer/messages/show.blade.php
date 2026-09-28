<x-layouts.panel :title="$conversation->farmer->stall_name">

    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('customer.messages.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to messages
        </a>
        <a href="{{ route('customer.farmers.show', $conversation->farmer) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">
            View stall
        </a>
    </div>

    @include('messages._thread', [
        'conversation' => $conversation,
        'otherPartyName' => $conversation->farmer->stall_name,
        'otherPartyAvatarUrl' => $conversation->farmer->coverImageUrl(),
        'sendAction' => route('customer.farmers.message', $conversation->farmer),
        'orderRouteName' => 'customer.orders.show',
    ])

    @push('scripts')
        @vite('resources/js/chat-composer.js')
    @endpush

</x-layouts.panel>
