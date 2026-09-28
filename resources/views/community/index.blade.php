@php
    // logged in user apne panel mein rehta hai, guest ko public site. panel ka <main> padding aur flash khud deta hai
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Community">

    <div x-data="{ showLoginPrompt: false }" @open-login-prompt.window="showLoginPrompt = true">
    <div @class(['mx-auto max-w-6xl', 'px-4 py-10 sm:px-8' => ! auth()->check()])>
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    {{-- hero banner - photo + dark green overlay, bas rounded aur page ke andar --}}
    <section class="relative isolate mb-8 flex min-h-[280px] items-center overflow-hidden rounded-[28px]">
        <img src="{{ asset('images/site/community-hero.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0" style="background: linear-gradient(rgba(10,30,15,0.6), rgba(10,30,15,0.78));"></div>

        <div class="relative mx-auto max-w-xl px-6 py-14 text-center text-white sm:px-12">
            <h1 class="font-display text-3xl font-semibold text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)] sm:text-4xl">
                Connect with your community
            </h1>
            <p class="mx-auto mt-3 text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">
                See what farmers and customers are sharing, ask questions, and find out what's fresh this week.
            </p>
            {{-- guest post nahi kar sakta, is liye composer ki jagah login --}}
            <a href="{{ auth()->check() ? '#composer' : route('login') }}" class="btn-glass-orange mt-6 px-6 py-3 text-base">Join the conversation</a>
        </div>
    </section>

    <div class="min-[900px]:grid min-[900px]:grid-cols-[280px_1fr] min-[900px]:items-start min-[900px]:gap-6">
        {{-- guidelines - desktop pe sticky, mobile pe accordion taake feed neeche na dhakelen --}}
        <aside class="glass mb-6 p-6 min-[900px]:sticky min-[900px]:top-[100px] min-[900px]:mb-0" x-data="{ guidelinesOpen: false }">
            <button type="button" @click="guidelinesOpen = ! guidelinesOpen" :aria-expanded="guidelinesOpen.toString()"
                    class="flex w-full items-center justify-between gap-3 text-left min-[900px]:pointer-events-none">
                <h2 class="font-display text-lg font-semibold text-soil">Posting guidelines</h2>
                <iconify-icon icon="tabler:chevron-down" class="shrink-0 text-soil-muted transition min-[900px]:hidden" :class="guidelinesOpen ? 'rotate-180' : ''"></iconify-icon>
            </button>

            <ul class="mt-4 space-y-4 text-sm min-[900px]:!block" x-show="guidelinesOpen">
                <li class="flex gap-3">
                    <span class="glass-icon-chip h-9 w-9 shrink-0 text-base text-leaf-600">
                        <iconify-icon icon="tabler:tractor"></iconify-icon>
                    </span>
                    <p class="text-soil-muted"><span class="font-medium text-soil">Farmers</span> - only post about your own stall: new stock, availability updates, or market-day news. Off-topic posts won't be approved.</p>
                </li>
                <li class="flex gap-3">
                    <span class="glass-icon-chip h-9 w-9 shrink-0 text-base text-tomato-600">
                        <iconify-icon icon="tabler:basket"></iconify-icon>
                    </span>
                    <p class="text-soil-muted"><span class="font-medium text-soil">Buyers</span> - ask questions, share what you're looking for, or talk about produce and recipes.</p>
                </li>
                <li class="flex gap-3">
                    <span class="glass-icon-chip h-9 w-9 shrink-0 text-base text-sky-700">
                        <iconify-icon icon="tabler:speakerphone"></iconify-icon>
                    </span>
                    <p class="text-soil-muted"><span class="font-medium text-soil">Admin</span> - official announcements, market schedule changes, and platform updates.</p>
                </li>
                <li class="flex gap-3">
                    <span class="glass-icon-chip h-9 w-9 shrink-0 text-base text-leaf-600">
                        <iconify-icon icon="tabler:shield-check"></iconify-icon>
                    </span>
                    <p class="text-soil-muted">Every post is reviewed - nothing appears publicly until an admin approves it, so the feed stays useful and spam-free.</p>
                </li>
            </ul>
        </aside>

        <div id="composer" class="min-w-0 scroll-mt-28">
            @auth
                <form method="POST" action="{{ route('community.posts.store') }}" enctype="multipart/form-data" class="glass mb-6 space-y-3 p-5">
                    @csrf
                    <textarea name="body" rows="3" placeholder="Share something with the community..." class="form-input"></textarea>
                    <x-input-error :messages="$errors->get('body')" />

                    <div class="flex items-center justify-between gap-3">
                        <label class="icon-chip h-10 w-10 cursor-pointer bg-white/50 text-soil-muted hover:text-soil" title="Attach a photo">
                            <iconify-icon icon="tabler:paperclip"></iconify-icon>
                            <input type="file" name="image" accept="image/*" class="hidden">
                        </label>
                        <button type="submit" class="btn-glass-orange">
                            <iconify-icon icon="tabler:send-2"></iconify-icon>
                            Post
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('image')" />
                </form>
            @else
                <div class="glass mb-6 flex flex-col items-start gap-4 p-5 sm:flex-row sm:items-center">
                    <span class="glass-icon-chip text-tomato-600">
                        <iconify-icon icon="tabler:lock"></iconify-icon>
                    </span>
                    <div class="flex-1">
                        <p class="font-medium text-soil">Log in to post</p>
                        <p class="text-sm text-soil-muted">You need an account to share a post, comment or like.</p>
                    </div>
                    <a href="{{ route('login') }}" class="btn-glass-orange shrink-0">Log in</a>
                </div>
            @endauth

            @if ($viewerPendingPosts->isNotEmpty())
                <div class="mb-6 space-y-3">
                    @foreach ($viewerPendingPosts as $pendingPost)
                        @include('community._post', ['post' => $pendingPost])
                    @endforeach
                </div>
            @endif

            @if ($posts->isEmpty())
                <div class="glass p-6">
                    <x-ui.empty-state icon="tabler:messages" title="No posts yet" message="Be the first to share something with the community." />
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($posts as $post)
                        @include('community._post', ['post' => $post])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </div>
    </div>

    {{-- upar ke guest wale post / like / comment buttons isay fire karte hain --}}
    <div x-show="showLoginPrompt" x-transition x-cloak
         class="fixed inset-x-0 bottom-6 z-50 mx-auto flex w-fit max-w-[92vw] items-center gap-3 rounded-full bg-soil px-5 py-3 text-sm text-white shadow-xl">
        <iconify-icon icon="tabler:lock" class="shrink-0 text-lg text-white/70"></iconify-icon>
        <span>Please log in to post, comment or like.</span>
        <a href="{{ route('login') }}" class="btn-accent shrink-0 px-3 py-1.5 text-xs">Log in</a>
        <button type="button" @click="showLoginPrompt = false" class="shrink-0 text-white/60 hover:text-white" aria-label="Dismiss">
            <iconify-icon icon="tabler:x"></iconify-icon>
        </button>
    </div>
    </div>

</x-dynamic-component>
