<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Notifications\Messages\MailMessage;

class WorkflowSubmittedNotification extends BaseGedNotification
{
    public const TYPE = 'workflow.submitted';

    public function __construct(
        public Document $document,
        public User $actor,
        public WorkflowInstance $instance,
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
            'title' => 'Document soumis pour validation',
            'message' => "Le document '{$this->document->name}' a été soumis pour validation.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'workflow_instance_id' => $this->instance->id,
            'workflow_id' => $this->instance->workflow_id,
            'step_id' => $this->step?->id,
            'step_name' => $this->step?->name,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => "/workflow-instances/{$this->instance->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Document soumis : {$this->document->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le document '{$this->document->name}' a été soumis pour validation par {$this->actor->name}.")
            ->action('Consulter l\'instance', url("/workflow-instances/{$this->instance->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
