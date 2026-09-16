<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Notifications\Messages\MailMessage;

class WorkflowCorrectionRequestedNotification extends BaseGedNotification
{
    public const TYPE = 'workflow.correction_requested';

    public function __construct(
        public Document $document,
        public User $actor,
        public WorkflowInstance $instance,
        public string $comment,
        public ?WorkflowStep $step = null
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => 'Correction demandée',
            'message' => "Des corrections sont demandées par {$this->actor->name} sur le document '{$this->document->name}'. Commentaire : {$this->comment}",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'workflow_instance_id' => $this->instance->id,
            'step_id' => $this->step?->id,
            'step_name' => $this->step?->name,
            'comment' => $this->comment,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => "/workflow-instances/{$this->instance->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Correction demandée : {$this->document->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Des corrections sont demandées sur le document '{$this->document->name}' par {$this->actor->name}.")
            ->line("Commentaire : {$this->comment}")
            ->action('Consulter le document', url("/workflow-instances/{$this->instance->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
