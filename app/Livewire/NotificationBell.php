<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    #[On('refresh-notifications')]
    public function refreshNotifications(): void
    {
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.notification-bell', [
            'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
            'recentNotifications' => $user?->unreadNotifications()->latest()->limit(6)->get() ?? collect(),
            'userId' => $user?->getKey(),
        ]);
    }
}
