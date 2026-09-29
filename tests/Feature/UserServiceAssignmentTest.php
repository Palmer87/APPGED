<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Organization;
use App\Models\Service;
use App\Models\User;
use App\Services\OrganizationStructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $admin;

    protected User $user;

    protected Direction $direction;

    protected Service $service1;

    protected Service $service2;

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

        $this->service1 = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Comptabilité',
        ]);

        $this->service2 = Service::factory()->create([
            'organization_id' => $this->org->id,
            'direction_id' => $this->direction->id,
            'name' => 'Trésorerie',
        ]);
    }

    public function test_can_assign_user_to_service(): void
    {
        $this->structureService->assignUserToService($this->user, $this->service1, false, $this->admin);

        $this->assertTrue($this->user->services()->where('services.id', $this->service1->id)->exists());
        $this->assertNull($this->user->fresh()->primary_service_id);
    }

    public function test_can_assign_user_with_primary_service(): void
    {
        $this->structureService->assignUserToService($this->user, $this->service1, true, $this->admin);

        $this->assertTrue($this->user->services()->where('services.id', $this->service1->id)->exists());
        $this->assertEquals($this->service1->id, $this->user->fresh()->primary_service_id);
    }

    public function test_can_remove_user_from_service(): void
    {
        $this->structureService->assignUserToService($this->user, $this->service1, true, $this->admin);
        $this->assertEquals($this->service1->id, $this->user->fresh()->primary_service_id);

        $this->structureService->removeUserFromService($this->user, $this->service1, $this->admin);

        $this->assertFalse($this->user->services()->where('services.id', $this->service1->id)->exists());
        $this->assertNull($this->user->fresh()->primary_service_id);
    }
}
