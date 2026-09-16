<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Notifications\Messages\MailMessage;

class WorkflowStepAssignedNotification extends BaseGedNotification
{
    public const TYPE = 'workflow.step_assigned';

    public function __construct(
        public Document $document,
        public User $actor,
        public WorkflowInstance $instance,
        public WorkflowStep $step
    ) {}

    public function getType(): string
    {
        return self::TYPE;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'title' => 'Étape de workflow à valider',
            'message' => "Vous devez valider l'étape '{$this->step->name}' pour le document '{$this->document->name}'.",
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'workflow_instance_id' => $this->instance->id,
            'step_id' => $this->step->id,
            'step_name' => $this->step->name,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => "/workflow-instances/{$this->instance->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Validation requise : {$this->step->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous devez valider l'étape '{$this->step->name}' pour le document '{$this->document->name}'.")
            ->action('Traiter la validation', url("/workflow-instances/{$this->instance->id}"))
            ->line('Merci d\'utiliser notre service GED.');
    }
}
