<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowInstance>
 */
class WorkflowInstanceFactory extends Factory
{
    protected $model = WorkflowInstance::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'workflow_id' => Workflow::factory(),
            'document_id' => Document::factory(),
            'document_version_id' => null,
            'started_by' => User::factory(),
            'current_step_id' => null,
            'status' => WorkflowStatus::InProgress,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::InProgress,
            'completed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::Approved,
            'completed_at' => now(),
            'current_step_id' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::Rejected,
            'completed_at' => now(),
            'current_step_id' => null,
        ]);
    }

    public function correctionRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::CorrectionRequested,
            'completed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::Cancelled,
            'completed_at' => now(),
            'current_step_id' => null,
        ]);
    }
}
