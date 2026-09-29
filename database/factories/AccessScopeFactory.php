<?php

namespace Database\Factories;

use App\Enums\AccessScopeType;
use App\Models\AccessScope;
use App\Models\Direction;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessScope>
 */
class AccessScopeFactory extends Factory
{
    protected $model = AccessScope::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'scope_type' => AccessScopeType::Organization,
            'direction_id' => null,
            'service_id' => null,
            'folder_id' => null,
            'document_id' => null,
            'is_active' => true,
        ];
    }

    public function forDirection(Direction $direction): static
    {
        return $this->state(fn () => [
            'organization_id' => $direction->organization_id,
            'scope_type' => AccessScopeType::Direction,
            'direction_id' => $direction->id,
            'service_id' => null,
            'folder_id' => null,
            'document_id' => null,
        ]);
    }

    public function forService(Service $service): static
    {
        return $this->state(fn () => [
            'organization_id' => $service->organization_id,
            'scope_type' => AccessScopeType::Service,
            'direction_id' => $service->direction_id,
            'service_id' => $service->id,
            'folder_id' => null,
            'document_id' => null,
        ]);
    }
}
