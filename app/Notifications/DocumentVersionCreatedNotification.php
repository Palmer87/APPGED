<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentVersionCreatedNotification extends BaseGedNotification
{
    public const TYPE = 'document.version_created';

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document,
        public DocumentVersion $version,
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
            'title' => 'Nouvelle version déposée',
            'message' => "La version {$this->version->version_number} du document '{$this->document->name}' a été déposée par {$this->actor->name}.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'version_id' => $this->version->id,
            'version_number' => $this->version->version_number,
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
            ->subject("Nouvelle version : {$this->document->name} (v{$this->version->version_number})")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle version (v{$this->version->version_number}) du document '{$this->document->name}' a été déposée par {$this->actor->name}.")
            ->action('Consulter le document', url("/documents/{$this->document->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
