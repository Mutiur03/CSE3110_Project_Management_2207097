<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        return response()->json($this->notificationPayload($request));
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json($this->notificationPayload($request));
        }

        return back()->with('status', 'Notifications marked as read.');
    }

    private function notificationPayload(Request $request): array
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->limit(6)
            ->get();

        return [
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => data_get($notification->data, 'title', 'Project update'),
                'message' => data_get($notification->data, 'message'),
                'url' => data_get($notification->data, 'url', '#'),
                'created_at' => $notification->created_at?->toIso8601String(),
                'created_at_human' => $notification->created_at?->diffForHumans(),
            ])->values(),
        ];
    }
}
