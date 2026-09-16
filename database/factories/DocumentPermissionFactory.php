<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentPermission>
 */
class DocumentPermissionFactory extends Factory
{
    protected $model = DocumentPermission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => fn (array $attributes) => User::factory()->create([
                'organization_id' => Document::find($attributes['document_id'])?->organization_id ?? Organization::factory(),
            ])->id,
            'group_id' => null,
            'permission' => 'view',
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'group_id' => null,
        ]);
    }

    public function forGroup(Group $group): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'group_id' => $group->id,
        ]);
    }

    public function forPermission(string $permission): static
    {
        return $this->state(fn (array $attributes) => [
            'permission' => $permission,
        ]);
    }
}
