@php
    // sirf logged in viewer pe set hota hai (FeedController) - guest ke liye "not liked"
    $postIsLikedByViewer = $post->liked_by_viewer ?? false;

    // apni pending posts sirf author ko milti hain (FeedController)
    $postIsPending = ! $post->isApproved();
@endphp

<article class="glass p-5" x-data="{ commentsOpen: false }">
    <div class="flex items-center gap-3">
        <span class="icon-chip h-10 w-10 bg-leaf-50 text-sm font-semibold text-leaf-700">
            {{ strtoupper(substr($post->author->name, 0, 1)) }}
        </span>
        <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2 font-medium text-soil">
                {{ $post->author->name }}
                <span @class(['rounded-full border px-2.5 py-0.5 text-xs font-medium', $post->author->role->pillClasses()])>
                    {{ $post->author->role->label() }}
                </span>
                @if ($post->is_pinned)
                    <span class="badge-orange gap-1">
                        <iconify-icon icon="tabler:pin-filled"></iconify-icon>
                        Pinned
                    </span>
                @endif
                @if ($postIsPending)
                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-50/80 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                        <iconify-icon icon="tabler:clock"></iconify-icon>
                        Pending approval
                    </span>
                @endif
            </p>
            <p class="text-xs text-soil-muted">{{ $post->created_at->diffForHumans() }}</p>
        </div>
    </div>

    <p class="mt-4 whitespace-pre-line text-soil">{{ $post->body }}</p>

    @if ($post->image_path)
        <img src="{{ $post->imageUrl() }}" alt="Photo shared by {{ $post->author->name }}"
             class="mt-4 max-h-96 w-full rounded-xl object-cover">
    @endif

    {{-- likes aur comments sirf approved post pe - us se pehle routes 404 dete hain --}}
    @if ($postIsPending)
        <p class="mt-4 border-t border-cream-dark pt-3 text-xs text-soil-muted">Only you can see this until an admin approves it.</p>
    @else
    <div class="mt-4 flex items-center gap-2 border-t border-cream-dark pt-3 text-sm">
        @auth
            <form method="POST" action="{{ route('community.posts.like', $post) }}">
                @csrf
                <button type="submit" @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition',
                    'bg-tomato-500/10 text-tomato-700' => $postIsLikedByViewer,
                    'text-soil-muted hover:bg-cream' => ! $postIsLikedByViewer,
                ])>
                    <iconify-icon icon="{{ $postIsLikedByViewer ? 'tabler:heart-filled' : 'tabler:heart' }}"></iconify-icon>
                    {{ $post->likes_count }}
                </button>
            </form>
        @else
            <button type="button" @click="$dispatch('open-login-prompt')" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium text-soil-muted transition hover:bg-cream">
                <iconify-icon icon="tabler:heart"></iconify-icon>
                {{ $post->likes_count }}
            </button>
        @endauth

        <button type="button" @click="commentsOpen = ! commentsOpen" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium text-soil-muted transition hover:bg-cream">
            <iconify-icon icon="tabler:message-circle"></iconify-icon>
            {{ $post->comments_count }} {{ \Illuminate\Support\Str::plural('comment', $post->comments_count) }}
        </button>
    </div>

    <div x-show="commentsOpen" x-cloak class="mt-3 space-y-3 border-t border-cream-dark pt-3">
        @forelse ($post->comments as $comment)
            <div class="flex gap-2 text-sm">
                <span class="icon-chip h-7 w-7 shrink-0 bg-cream text-xs font-semibold text-soil">
                    {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p><span class="font-medium">{{ $comment->user->name }}</span>
                        <span class="text-soil-muted">{{ $comment->body }}</span></p>
                    <p class="text-xs text-soil-muted">{{ $comment->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-soil-muted">No comments yet.</p>
        @endforelse

        @auth
            <form method="POST" action="{{ route('community.posts.comments.store', $post) }}" class="flex items-center gap-2 pt-2">
                @csrf
                <input type="text" name="body" placeholder="Write a comment..." class="form-input flex-1">
                <button type="submit" class="btn-outline shrink-0">Reply</button>
            </form>
        @else
            <div class="flex items-center gap-2 pt-2 cursor-pointer" @click="$dispatch('open-login-prompt')">
                <input type="text" placeholder="Log in to write a comment..." class="form-input flex-1" readonly>
                <span class="btn-outline shrink-0 pointer-events-none">Reply</span>
            </div>
        @endauth
    </div>
    @endif
</article>
