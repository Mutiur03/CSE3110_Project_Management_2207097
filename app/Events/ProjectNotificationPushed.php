<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ProjectNotificationPushed implements ShouldBroadcastNow
{
    public function __construct(
        public readonly string $userId,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("App.Models.User.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'project.notification.pushed';
    }
}
