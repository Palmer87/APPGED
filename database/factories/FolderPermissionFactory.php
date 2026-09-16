<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\FolderPermission;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolderPermission>
 */
class FolderPermissionFactory extends Factory
{
    protected $model = FolderPermission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folder_id' => Folder::factory(),
            'user_id' => fn (array $attributes) => User::factory()->create([
                'organization_id' => Folder::find($attributes['folder_id'])?->organization_id ?? Organization::factory(),
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
