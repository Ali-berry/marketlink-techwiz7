@php
    $tabLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
@endphp

<x-layouts.panel title="Community moderation">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Community moderation</h2>
        <p class="mt-1 text-sm text-soil-muted">Review posts before they reach the community feed, and keep the feed tidy afterwards.</p>
    </div>

    {{-- sirf community ke numbers - platform data admin dashboard pe hai jo Community Moderator nahi khol sakta --}}
    <section class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Waiting for review" :value="$communityStats['waiting']" icon="tabler:clock" tone="amber" />
        <x-ui.stat-card label="Approved this week" :value="$communityStats['approved_this_week']" icon="tabler:circle-check" />
        <x-ui.stat-card label="Pinned posts" :value="$communityStats['pinned'].' / '.$maxPinnedPosts" icon="tabler:pin" tone="tomato" />
        <x-ui.stat-card label="Rejected this week" :value="$communityStats['rejected_this_week']" icon="tabler:circle-x" tone="sky" />
    </section>

    <div class="tab-bar">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            <a href="{{ route('admin.community.index', ['tab' => $tabKey]) }}"
               @class(['tab-pill', 'is-active' => $activeTab === $tabKey])>{{ $tabLabel }} ({{ $tabCounts[$tabKey] }})</a>
        @endforeach
    </div>

    @if ($posts->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:message-check" :title="match ($activeTab) {
                'pending' => 'Nothing waiting for review',
                'approved' => 'No approved posts yet',
                'rejected' => 'No rejected posts',
            }" message="New community posts show up on the Pending tab first." />
        </div>
    @else
        <div class="space-y-4">
            @foreach ($posts as $post)
                <article class="card p-5">
                    <div class="flex items-center gap-3">
                        <span class="glass-icon-chip h-10 w-10 text-sm font-semibold text-leaf-700">
                            {{ strtoupper(substr($post->author->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-medium">
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
                            </p>
                            <p class="text-xs text-soil-muted">{{ $post->created_at->format('j M Y, g:i A') }} ({{ $post->created_at->diffForHumans() }})</p>
                        </div>
                        <p class="flex shrink-0 items-center gap-3 text-xs text-soil-muted">
                            <span class="flex items-center gap-1"><iconify-icon icon="tabler:heart"></iconify-icon> {{ $post->likes_count }}</span>
                            <span class="flex items-center gap-1"><iconify-icon icon="tabler:message-circle"></iconify-icon> {{ $post->comments_count }}</span>
                        </p>
                    </div>

                    <p class="mt-4 whitespace-pre-line text-soil">{{ $post->body }}</p>

                    @if ($post->image_path)
                        <img src="{{ $post->imageUrl() }}" alt="Photo shared by {{ $post->author->name }}"
                             class="mt-4 max-h-72 w-full rounded-xl object-cover">
                    @endif

                    @if ($post->status === \App\Enums\CommunityPostStatus::Rejected && $post->rejection_reason)
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50/80 px-3 py-2.5 text-sm text-red-800">
                            <span class="font-medium">Rejected:</span> {{ $post->rejection_reason }}
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-white/60 pt-4">
                        @if ($post->status !== \App\Enums\CommunityPostStatus::Approved)
                            <form method="POST" action="{{ route('admin.community.approve', $post) }}">
                                @csrf
                                <button type="submit" class="btn-glass-orange">
                                    <iconify-icon icon="tabler:check"></iconify-icon>
                                    {{ $post->status === \App\Enums\CommunityPostStatus::Rejected ? 'Approve anyway' : 'Approve' }}
                                </button>
                            </form>
                        @endif

                        @if ($post->status === \App\Enums\CommunityPostStatus::Pending)
                            {{-- reason khali chhoda to error ke saath khud dobara khulta hai --}}
                            <x-ui.confirm-dialog title="Reject this post?"
                                                  body="It stays out of the community feed. The author gets a notification with your reason."
                                                  :action="route('admin.community.reject', $post)" confirm-label="Reject post"
                                                  :open-by-default="old('rejecting_post_id') == $post->id">
                                <x-slot:trigger>
                                    <span class="btn-danger cursor-pointer">Reject</span>
                                </x-slot:trigger>
                                <x-slot:fields>
                                    <input type="hidden" name="rejecting_post_id" value="{{ $post->id }}">
                                    <label class="form-label" for="reason-{{ $post->id }}">Reason for the author</label>
                                    <textarea id="reason-{{ $post->id }}" name="reason" rows="3" class="form-input" required
                                              placeholder="e.g. Off-topic - please only post about your own stall">{{ old('rejecting_post_id') == $post->id ? old('reason') : '' }}</textarea>
                                    @if (old('rejecting_post_id') == $post->id)
                                        <x-input-error :messages="$errors->get('reason')" class="mt-1.5" />
                                    @endif
                                </x-slot:fields>
                            </x-ui.confirm-dialog>
                        @endif

                        @if ($post->status === \App\Enums\CommunityPostStatus::Approved)
                            <form method="POST" action="{{ route($post->is_pinned ? 'admin.community.unpin' : 'admin.community.pin', $post) }}">
                                @csrf
                                <button type="submit" class="btn-outline">
                                    <iconify-icon icon="{{ $post->is_pinned ? 'tabler:pinned-off' : 'tabler:pin' }}"></iconify-icon>
                                    {{ $post->is_pinned ? 'Unpin' : 'Pin to top' }}
                                </button>
                            </form>
                        @endif

                        @if ($post->status !== \App\Enums\CommunityPostStatus::Pending)
                            <x-ui.confirm-dialog title="Delete this post?"
                                                  body="The post, its comments, likes and photo are removed for good."
                                                  :action="route('admin.community.destroy', $post)" method="DELETE" confirm-label="Delete post">
                                <x-slot:trigger>
                                    <span class="btn-danger cursor-pointer">
                                        <iconify-icon icon="tabler:trash"></iconify-icon>
                                        Delete
                                    </span>
                                </x-slot:trigger>
                            </x-ui.confirm-dialog>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $posts->links() }}
        </div>
    @endif

</x-layouts.panel>
