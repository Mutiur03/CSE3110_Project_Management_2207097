<?php

namespace App\Notifications;

use App\Events\ProjectNotificationPushed;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Throwable;

class ProjectEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly string $url,
        private readonly ?string $projectId = null,
        private readonly ?string $issueId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function sendTo(object $notifiable): void
    {
        $notifiable->notify($this);

        try {
            event(new ProjectNotificationPushed((string) $notifiable->getKey()));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function payload(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'project_id' => $this->projectId,
            'issue_id' => $this->issueId,
        ];
    }
}
