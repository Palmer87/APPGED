<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_has_many_users(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $this->assertSame($organization->id, $user->organization->id);
        $this->assertTrue($organization->users()->whereKey($user)->exists());
    }

    public function test_user_and_group_are_related_within_an_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $group = Group::factory()->for($organization)->create();

        $user->groups()->attach($group);

        $this->assertTrue($user->groups()->whereKey($group)->exists());
        $this->assertTrue($group->users()->whereKey($user)->exists());
        $this->assertSame($organization->id, $group->organization->id);
    }
}
