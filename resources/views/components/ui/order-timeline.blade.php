@props([
    'statusChanges',
    // farmer ko dikhta hai change kisne kiya, customer ko zaroorat nahi
    'showWhoChanged' => false,
    // AI chat se laga order - "Placed" line pe likha aata hai
    'placedViaAiChat' => false,
])

{{-- purana change pehle. AI ke changes (auto-confirm, auto-ready) dono taraf "by MarketLink AI" dikhte hain.
     AI chat se laga order pehli line pe "by Sara Ahmed via MarketLink AI" --}}
<ul class="mt-4 space-y-4">
    @foreach ($statusChanges->reverse() as $statusChange)
        @php
            $madeByMarketLinkAi = $statusChange->wasMadeByMarketLinkAi();
            $placedThroughAiChat = $placedViaAiChat && $statusChange->from_status === null;
        @endphp
        <li class="flex gap-3">
            <span @class([
                'icon-chip h-8 w-8 text-sm',
                'bg-tomato-50 text-tomato-600' => $madeByMarketLinkAi || $placedThroughAiChat,
                'bg-leaf-50 text-leaf-600' => ! $madeByMarketLinkAi && ! $placedThroughAiChat,
            ])>
                <iconify-icon icon="{{ $madeByMarketLinkAi || $placedThroughAiChat ? 'tabler:sparkles' : 'tabler:circle-check' }}"></iconify-icon>
            </span>
            <div>
                <p class="text-sm font-medium">
                    @if ($statusChange->from_status)
                        {{ \App\Enums\OrderStatus::from($statusChange->from_status)->label() }} &rarr;
                    @endif
                    {{ \App\Enums\OrderStatus::from($statusChange->to_status)->label() }}
                </p>
                <p class="text-xs text-soil-muted">
                    {{ $statusChange->created_at->format('D j M, g:i A') }}
                    @if ($madeByMarketLinkAi)
                        <span class="font-medium text-tomato-700">by MarketLink AI</span>
                    @elseif ($placedThroughAiChat)
                        <span class="font-medium text-tomato-700">by {{ $statusChange->changedBy?->name }} via MarketLink AI</span>
                    @elseif ($showWhoChanged && $statusChange->changedBy)
                        by {{ $statusChange->changedBy->name }}
                    @endif
                </p>
                @if ($statusChange->note)
                    <p class="mt-1 text-sm italic text-soil-muted">"{{ $statusChange->note }}"</p>
                @endif
            </div>
        </li>
    @endforeach
</ul>
