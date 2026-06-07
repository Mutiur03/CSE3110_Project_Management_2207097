<div class="relative" data-notification-menu data-notifications-user-id="{{ $userId }}">
    <button type="button" data-notification-button
        class="relative grid size-10 place-items-center rounded-md border border-neutral-200 bg-white text-neutral-700 transition hover:border-neutral-950 hover:text-neutral-950">
        <span class="sr-only">Notifications</span>
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
            stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M14.75 18.5a2.75 2.75 0 0 1-5.5 0M18 9.5a6 6 0 1 0-12 0c0 7-2.25 7.5-2.25 7.5h16.5S18 16.5 18 9.5Z" />
        </svg>
        <span
            class="{{ $unreadNotificationCount > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white">
            {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
        </span>
    </button>

    <div data-notification-panel
        class="absolute right-0 top-full z-50 mt-2 hidden w-80 overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg">
        <div class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3">
            <p class="text-sm font-bold text-neutral-950">Notifications</p>
            @if ($unreadNotificationCount > 0)
                <form wire:submit.prevent="markAllRead">
                    <button type="submit" class="text-xs font-bold text-blue-600 underline-offset-4 hover:underline">
                        Mark read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($recentNotifications as $notification)
                <a href="{{ data_get($notification->data, 'url', '#') }}" wire:navigate
                    class="block border-b border-neutral-100 px-4 py-3 transition last:border-b-0 hover:bg-stone-50">
                    <p class="text-sm font-bold text-neutral-950">{{ data_get($notification->data, 'title', 'Project update') }}</p>
                    <p class="mt-1 text-xs leading-5 text-neutral-600">{{ data_get($notification->data, 'message') }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-neutral-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-6 text-sm text-neutral-500">No unread notifications.</p>
            @endforelse
        </div>
    </div>
</div>
