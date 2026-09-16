<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentCommentedNotification extends BaseGedNotification
{
    public const TYPE = 'document.commented';

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document,
        public User $actor,
        public string $commentPreview = ''
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
            'title' => 'Nouveau commentaire',
            'message' => "Un commentaire a été ajouté sur le document '{$this->document->name}' par {$this->actor->name}.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'comment_preview' => $this->commentPreview,
            'url' => "/documents/{$this->document->id}",
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouveau commentaire : {$this->document->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->actor->name} a commenté le document '{$this->document->name}'.")
            ->line("Extrait : \"{$this->commentPreview}\"")
            ->action('Voir le document', url("/documents/{$this->document->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
