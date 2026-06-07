<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ProjectEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_unread_notification_feed(): void
    {
        $user = User::factory()->create();

        (new ProjectEventNotification(
            'Issue assigned',
            'A task was assigned to you.',
            route('dashboard'),
        ))->sendTo($user);

        $response = $this->actingAs($user)->getJson(route('notifications.feed'));

        $response
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Issue assigned')
            ->assertJsonPath('notifications.0.message', 'A task was assigned to you.')
            ->assertJsonPath('notifications.0.url', route('dashboard'));
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new ProjectEventNotification(
            'New comment',
            'Someone commented on your issue.',
            route('dashboard'),
        ));

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)->post(route('notifications.read'));

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_mark_notifications_as_read_without_reload(): void
    {
        $user = User::factory()->create();

        $user->notify(new ProjectEventNotification(
            'New comment',
            'Someone commented on your issue.',
            route('dashboard'),
        ));

        $response = $this->actingAs($user)->postJson(route('notifications.read'));

        $response
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_livewire_notification_bell_can_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new ProjectEventNotification(
            'New comment',
            'Someone commented on your issue.',
            route('dashboard'),
        ));

        Livewire::actingAs($user)
            ->test('notification-bell')
            ->assertSee('New comment')
            ->call('markAllRead')
            ->assertSee('No unread notifications.');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }
}
