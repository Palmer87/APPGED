<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Notifications\Messages\MailMessage;

class WorkflowApprovedNotification extends BaseGedNotification
{
    public const TYPE = 'workflow.approved';

    public function __construct(
        public Document $document,
        public User $actor,
        public WorkflowInstance $instance,
        public ?WorkflowStep $step = null,
        public bool $isFinal = false
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => $this->isFinal ? 'Workflow validé avec succès' : 'Étape de workflow validée',
            'message' => $this->isFinal
                ? "Le document '{$this->document->name}' a été définitivement validé."
                : "L'étape '{$this->step?->name}' du document '{$this->document->name}' a été validée par {$this->actor->name}.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'workflow_instance_id' => $this->instance->id,
            'step_id' => $this->step?->id,
            'step_name' => $this->step?->name,
            'is_final' => $this->isFinal,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => "/workflow-instances/{$this->instance->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->isFinal
            ? "Workflow validé : {$this->document->name}"
            : "Étape validée : {$this->document->name}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->isFinal
                ? "Le document '{$this->document->name}' a été définitivement validé."
                : "L'étape '{$this->step?->name}' a été validée par {$this->actor->name}.")
            ->action('Consulter l\'instance', url("/workflow-instances/{$this->instance->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
