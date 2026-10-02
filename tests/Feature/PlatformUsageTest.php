<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformUsageTest extends TestCase
{
    use RefreshDatabase;

    protected PlatformUser $owner;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = PlatformUser::create([
            'name' => 'Owner',
            'email' => 'owner@platform.test',
            'password' => Hash::make('password'),
            'role' => 'platform_owner',
            'is_active' => true,
        ]);

        $this->org = Organization::create([
            'name' => 'Usage Org',
            'slug' => 'usage-org',
            'status' => 'active',
        ]);
    }

    public function test_can_view_usage_dashboard(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/usage');

        $response->assertOk();
    }

    public function test_can_filter_usage_by_organization_name(): void
    {
        $response = $this->actingAs($this->owner, 'platform')
            ->get('/platform/usage?search=Usage');

        $response->assertOk();
    }
}
