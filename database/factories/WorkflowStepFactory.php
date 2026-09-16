<?php

namespace Database\Factories;

use App\Enums\WorkflowApproverType;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'workflow_id' => Workflow::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'position' => 1,
            'approver_type' => WorkflowApproverType::User,
            'approver_user_id' => User::factory(),
            'approver_group_id' => null,
            'is_required' => true,
        ];
    }

    public function forUserApprover(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_type' => WorkflowApproverType::User,
            'approver_user_id' => $user->id,
            'approver_group_id' => null,
        ]);
    }

    public function forGroupApprover(Group $group): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_type' => WorkflowApproverType::Group,
            'approver_user_id' => null,
            'approver_group_id' => $group->id,
        ]);
    }

    public function forWorkflow(Workflow $workflow, int $position = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $workflow->organization_id,
            'workflow_id' => $workflow->id,
            'position' => $position,
        ]);
    }
}
