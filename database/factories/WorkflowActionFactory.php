<?php

namespace Database\Factories;

use App\Enums\WorkflowActionType;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowAction>
 */
class WorkflowActionFactory extends Factory
{
    protected $model = WorkflowAction::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'workflow_instance_id' => WorkflowInstance::factory(),
            'workflow_step_id' => null,
            'user_id' => User::factory(),
            'document_version_id' => null,
            'action' => WorkflowActionType::Submitted,
            'comment' => null,
            'created_at' => now(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => WorkflowActionType::Submitted,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => WorkflowActionType::Approved,
        ]);
    }

    public function rejected(string $comment = 'Rejeté pour non-conformité'): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => WorkflowActionType::Rejected,
            'comment' => $comment,
        ]);
    }

    public function correctionRequested(string $comment = 'Merci de corriger la clause 3'): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => WorkflowActionType::CorrectionRequested,
            'comment' => $comment,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => WorkflowActionType::Cancelled,
        ]);
    }
}
