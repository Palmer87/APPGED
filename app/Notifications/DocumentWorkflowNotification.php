<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class DocumentWorkflowNotification extends BaseGedNotification
{
    public const TYPE = 'document.workflow';

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document,
        public User $actor,
        public string $workflowStatus = 'pending_approval',
        public ?string $comment = null
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
            'title' => 'Statut de validation mis à jour',
            'message' => "Le statut du document '{$this->document->name}' a évolué vers '{$this->workflowStatus}' par {$this->actor->name}.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'workflow_status' => $this->workflowStatus,
            'comment' => $this->comment,
            'url' => "/documents/{$this->document->id}",
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Workflow : {$this->document->name} ({$this->workflowStatus})")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le workflow du document '{$this->document->name}' est passé au statut '{$this->workflowStatus}' par {$this->actor->name}.")
            ->action('Consulter le document', url("/documents/{$this->document->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
