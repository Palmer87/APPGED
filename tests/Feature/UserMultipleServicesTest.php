<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\OrganizationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMultipleServicesTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $user;

    protected Direction $direction;

    protected Service $compta;

    protected Service $finance;

    protected Service $tresorerie;

    protected OrganizationStructureService $structureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureService = app(OrganizationStructureService::class);
        $this->org = Organization::factory()->create();

        $this->admin = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->direction = Direction::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'DAF',
        ]);

        $this->compta = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Comptabilité',
        ]);

        $this->finance = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Finance',
        ]);

        $this->tresorerie = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Trésorerie',
        ]);
    }

    public function test_user_can_belong_to_multiple_services(): void
    {
        $this->structureService->syncUserServices(
            user: $this->user,
            primaryServiceId: $this->compta->id,
            associatedServiceIds: [$this->finance->id, $this->tresorerie->id],
            actor: $this->admin
        );

        $freshUser = $this->user->fresh();

        $this->assertCount(3, $freshUser->services);
        $this->assertEquals($this->compta->id, $freshUser->primary_service_id);
        $this->assertTrue($freshUser->services->contains('id', $this->finance->id));
        $this->assertTrue($freshUser->services->contains('id', $this->tresorerie->id));
    }

    public function test_syncing_services_removes_unselected_services(): void
    {
        $this->structureService->syncUserServices(
            user: $this->user,
            primaryServiceId: $this->compta->id,
            associatedServiceIds: [$this->finance->id],
            actor: $this->admin
        );

        $this->assertCount(2, $this->user->fresh()->services);

        // Sync again with only finance
        $this->structureService->syncUserServices(
            user: $this->user,
            primaryServiceId: $this->finance->id,
            associatedServiceIds: [],
            actor: $this->admin
        );

        $freshUser = $this->user->fresh();
        $this->assertCount(1, $freshUser->services);
        $this->assertEquals($this->finance->id, $freshUser->primary_service_id);
        $this->assertFalse($freshUser->services->contains('id', $this->compta->id));
    }
}
