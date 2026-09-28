@php
    $recentNotifications = auth()->user()->notifications()->latest()->take(8)->get();
    $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
@endphp

<div x-data="{ notificationsOpen: false }" class="relative">
    {{-- dark panel header mein hai, is liye bell light rang ki - dropdown white hi rehta hai --}}
    <button type="button" class="relative rounded-full p-2 text-cream/80 hover:bg-white/10 hover:text-white"
            @click="notificationsOpen = ! notificationsOpen" @click.outside="notificationsOpen = false"
            aria-label="Notifications">
        <iconify-icon icon="tabler:bell" class="text-xl"></iconify-icon>
        @if ($unreadNotificationCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-tomato-500 px-1 text-[10px] font-semibold text-white">
                {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
            </span>
        @endif
    </button>

    <div x-show="notificationsOpen" x-transition x-cloak
         class="absolute right-0 z-30 mt-2 w-80 max-w-[90vw] rounded-card border border-cream-dark bg-white p-2 shadow-xl">
        {{-- yahan colors khud set - warna white dropdown header ka white text le leta --}}
        <div class="flex items-center justify-between gap-3 border-b border-cream-dark px-3 py-2">
            <p class="text-sm font-medium text-soil">Notifications</p>
            @if ($unreadNotificationCount > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-leaf-600 hover:text-leaf-800">Mark all as read</button>
                </form>
            @endif
        </div>

        @if ($recentNotifications->isEmpty())
            <p class="px-3 py-6 text-center text-sm text-soil-muted">Nothing here yet.</p>
        @else
            <ul class="max-h-96 divide-y divide-cream-dark overflow-y-auto">
                @foreach ($recentNotifications as $notification)
                    @php $notificationBody = $notification->data['message'] ?? 'Update'; @endphp
                    <li>
                        @if ($notification->data['url'] ?? null)
                            <a href="{{ $notification->data['url'] }}" class="flex gap-2 px-3 py-3 hover:bg-cream">
                        @else
                            <div class="flex gap-2 px-3 py-3">
                        @endif

                            <span @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-tomato-500' => is_null($notification->read_at),
                                'bg-transparent' => ! is_null($notification->read_at),
                            ])></span>
                            <div class="min-w-0">
                                <p class="text-sm text-soil">{{ $notificationBody }}</p>
                                <p class="mt-0.5 text-xs text-soil-muted">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>

                        @if ($notification->data['url'] ?? null)
                            </a>
                        @else
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
