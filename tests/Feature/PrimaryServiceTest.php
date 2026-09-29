<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\OrganizationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $user;

    protected Direction $direction;

    protected Service $compta;

    protected Service $rh;

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

        $this->rh = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Recrutement',
        ]);
    }

    public function test_can_set_and_change_primary_service(): void
    {
        $this->structureService->assignPrimaryService($this->user, $this->compta, $this->admin);

        $freshUser = $this->user->fresh();
        $this->assertEquals($this->compta->id, $freshUser->primary_service_id);
        $this->assertEquals('Comptabilité', $freshUser->primaryService->name);
        $this->assertEquals('DAF', $freshUser->primaryService->direction->name);

        // Change primary service
        $this->structureService->assignPrimaryService($this->user, $this->rh, $this->admin);

        $freshUser2 = $this->user->fresh();
        $this->assertEquals($this->rh->id, $freshUser2->primary_service_id);
        $this->assertEquals('Recrutement', $freshUser2->primaryService->name);
    }
}
