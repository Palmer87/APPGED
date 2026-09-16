<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class BaseGedNotification extends Notification
{
    use Queueable;

    abstract public function getType(): string;

    /**
     * Get the notification's delivery channels based on user preferences.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! ($notifiable instanceof User)) {
            return ['database'];
        }

        $channels = [];
        $prefService = app(NotificationPreferenceService::class);

        if ($prefService->isDatabaseEnabled($notifiable, $this->getType())) {
            $channels[] = 'database';
        }

        if ($prefService->isEmailEnabled($notifiable, $this->getType())) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
