<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentShareRevokedNotification extends BaseGedNotification
{
    public const TYPE = 'document.share_revoked';

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document,
        public User $actor
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => 'Partage révoqué',
            'message' => "L'accès au document '{$this->document->name}' a été révoqué par {$this->actor->name}.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => "/documents/{$this->document->id}",
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Partage révoqué : {$this->document->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'accès au document '{$this->document->name}' a été révoqué par {$this->actor->name}.")
            ->line('Merci d\'utiliser notre service GED.');
    }
}
