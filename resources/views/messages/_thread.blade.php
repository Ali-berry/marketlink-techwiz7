{{--
    customer aur farmer dono ke thread pages - bas bubble ki side badalti hai. Chahiye: $conversation
    (messages.sender, messages.relatedOrder loaded), $otherPartyName, $otherPartyAvatarUrl (null ho to initials),
    $sendAction, $orderRouteName ("re: order" tag ka link)
--}}
<div class="card flex h-[calc(100vh-11rem)] flex-col overflow-hidden p-0">

    <div class="flex items-center gap-3 border-b border-cream-dark p-4">
        @if ($otherPartyAvatarUrl)
            <img src="{{ $otherPartyAvatarUrl }}" alt="{{ $otherPartyName }}" class="h-10 w-10 rounded-full object-cover object-[center_20%]">
        @else
            <span class="icon-chip h-10 w-10 bg-leaf-50 text-sm font-semibold text-leaf-700">
                {{ strtoupper(substr($otherPartyName, 0, 1)) }}
            </span>
        @endif
        <p class="font-medium">{{ $otherPartyName }}</p>
    </div>

    <div class="flex-1 space-y-3 overflow-y-auto p-4" data-chat-scroll-container>
        @forelse ($conversation->messages as $message)
            @php $isMine = $message->sender_id === auth()->id(); @endphp

            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}" x-data="{ imageOpen: false }">
                <div class="flex max-w-[80%] items-end gap-2 {{ $isMine ? 'flex-row-reverse' : '' }}">
                    <span @class([
                        'icon-chip h-8 w-8 shrink-0 text-xs font-semibold',
                        'bg-leaf-500 text-white' => $isMine,
                        'bg-cream text-soil' => ! $isMine,
                    ])>
                        {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                    </span>

                    <div class="min-w-0">
                        @if ($message->relatedOrder)
                            <a href="{{ route($orderRouteName, $message->relatedOrder) }}"
                               class="mb-1 inline-flex items-center gap-1 rounded-full bg-cream px-2.5 py-1 text-[11px] font-medium text-soil-muted hover:text-soil">
                                <iconify-icon icon="tabler:receipt"></iconify-icon>
                                Re: order {{ $message->relatedOrder->order_number }}
                            </a>
                        @endif

                        <div @class([
                            'space-y-2 rounded-2xl px-4 py-2.5 text-sm',
                            'rounded-br-sm bg-leaf-500 text-white' => $isMine,
                            'rounded-bl-sm border border-cream-dark bg-cream/60' => ! $isMine,
                        ])>
                            @if ($message->body)
                                <p class="whitespace-pre-line">{{ $message->body }}</p>
                            @endif

                            @if ($message->image_path)
                                <button type="button" @click="imageOpen = true" class="block">
                                    <img src="{{ $message->imageUrl() }}" alt="Shared photo"
                                         class="max-h-56 rounded-xl object-cover">
                                </button>

                                {{-- <body> mein teleport - .card ka blur fixed overlay ko andar band kar deta --}}
                                <template x-teleport="body">
                                    <div x-show="imageOpen" x-cloak x-transition.opacity @click="imageOpen = false"
                                         class="fixed inset-0 z-50 flex items-center justify-center bg-soil/80 p-6">
                                        <img src="{{ $message->imageUrl() }}" alt="Shared photo"
                                             class="max-h-[85vh] max-w-full rounded-card">
                                    </div>
                                </template>
                            @endif

                            @if ($message->voice_note_path)
                                <div class="flex items-center gap-2" data-voice-note-player>
                                    {{-- preload="auto" poori clip le aata hai (50-100KB), Range request wali trick ki zaroorat nahi --}}
                                    <audio controls preload="auto" src="{{ $message->voiceNoteUrl() }}" class="h-10 w-48 max-w-full"></audio>
                                    {{-- duration saved column se, player se nahi - Chrome ki webm file mein duration nahi hoti --}}
                                    @if ($message->voiceNoteDurationText())
                                        <span @class(['shrink-0 text-xs', 'text-white/80' => $isMine, 'text-soil-muted' => ! $isMine])>
                                            {{ $message->voiceNoteDurationText() }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <p @class(['mt-1 text-[11px] text-soil-muted', 'text-right' => $isMine])>
                            {{ $message->displayTime() }}
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <x-ui.empty-state icon="tabler:messages" title="No messages yet"
                               message="Say hello - the conversation starts as soon as you send the first message." />
        @endforelse
    </div>

    <form method="POST" action="{{ $sendAction }}" enctype="multipart/form-data" class="border-t border-cream-dark p-3">
        @csrf

        <div data-image-attach-preview hidden class="mb-2 flex items-center gap-2 rounded-xl bg-cream px-3 py-2 text-xs text-soil-muted">
            <iconify-icon icon="tabler:photo"></iconify-icon>
            <span data-image-attach-name class="flex-1 truncate"></span>
            <button type="button" data-image-attach-remove aria-label="Remove photo">
                <iconify-icon icon="tabler:x"></iconify-icon>
            </button>
        </div>

        <div data-voice-record-indicator hidden class="mb-2 flex items-center gap-2 rounded-xl bg-tomato-50 px-3 py-2 text-xs font-medium text-tomato-700">
            <span class="h-2 w-2 animate-pulse rounded-full bg-tomato-500"></span>
            Recording... <span data-voice-record-timer>0:00</span> - release to send
        </div>

        <div class="flex items-end gap-2">
            <label class="icon-chip h-10 w-10 cursor-pointer bg-cream text-soil-muted hover:text-soil" title="Attach a photo">
                <iconify-icon icon="tabler:paperclip"></iconify-icon>
                <input type="file" name="image" accept="image/*" data-image-attach-input class="hidden">
            </label>

            <textarea name="body" rows="1" placeholder="Write a message..."
                      class="form-input flex-1 resize-none py-2.5"></textarea>

            <input type="file" name="voice_note" accept="audio/*" data-voice-record-input class="hidden">
            <input type="hidden" name="voice_note_duration_seconds" data-voice-record-duration>
            <button type="button" data-voice-record-button
                    class="icon-chip h-10 w-10 shrink-0 bg-cream text-soil-muted transition hover:text-soil"
                    title="Hold to record a voice note" aria-label="Hold to record a voice note">
                <iconify-icon icon="tabler:microphone"></iconify-icon>
            </button>

            <button type="submit" class="btn-primary h-10 w-10 shrink-0 !px-0" aria-label="Send message">
                <iconify-icon icon="tabler:send-2"></iconify-icon>
            </button>
        </div>

        <x-input-error :messages="$errors->get('body')" class="mt-1.5" />
        <x-input-error :messages="$errors->get('image')" class="mt-1.5" />
        <x-input-error :messages="$errors->get('voice_note')" class="mt-1.5" />
    </form>
</div>
