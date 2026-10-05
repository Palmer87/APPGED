<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected Direction $dirA;

    protected Direction $dirB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::factory()->create(['name' => 'Org Alpha', 'status' => 'active']);
        $this->orgB = Organization::factory()->create(['name' => 'Org Beta', 'status' => 'active']);

        $this->dirA = Direction::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Direction Alpha',
        ]);
        $this->dirB = Direction::factory()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Direction Beta',
        ]);

        $this->userA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'alice@alpha.test',
            'password' => Hash::make('CorrectPassword123!'),
            'status' => 'active',
        ]);

        $this->userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'email' => 'bob@beta.test',
            'password' => Hash::make('CorrectPassword123!'),
            'status' => 'active',
        ]);
    }

    public function test_web_login_rate_limiting_locks_out_after_five_failed_attempts(): void
    {
        RateLimiter::clear('alice@alpha.test|127.0.0.1');

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'alice@alpha.test',
                'password' => 'WrongPassword!',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be throttled
        $throttledResponse = $this->post('/login', [
            'email' => 'alice@alpha.test',
            'password' => 'WrongPassword!',
        ]);

        $throttledResponse->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('second', strtolower($errorMessage));
    }

    public function test_api_login_rate_limiting_locks_out_after_five_failed_attempts(): void
    {
        RateLimiter::clear('alice@alpha.test|127.0.0.1');

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'alice@alpha.test',
                'password' => 'WrongPassword!',
            ]);
            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['email']);
        }

        // 6th attempt should be throttled
        $throttledResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@alpha.test',
            'password' => 'WrongPassword!',
        ]);

        $throttledResponse->assertStatus(422);
        $errors = $throttledResponse->json('errors.email.0');
        $this->assertStringContainsString('second', strtolower($errors));
    }

    public function test_cannot_create_service_with_direction_of_another_organization(): void
    {
        // Give userA permission to create services
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $adminRole = Role::findOrCreate('admin', 'web');
        $this->userA->assignRole($adminRole);

        // Attempt to create a service in orgA pointing to dirB (which belongs to orgB)
        $response = $this->actingAs($this->userA)->post('/services', [
            'name' => 'Malicious Service',
            'direction_id' => $this->dirB->id,
        ]);

        $response->assertSessionHasErrors('direction_id');
        $this->assertDatabaseMissing('services', [
            'name' => 'Malicious Service',
            'direction_id' => $this->dirB->id,
        ]);
    }

    public function test_cannot_create_access_scope_for_user_of_another_organization(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $adminRole = Role::findOrCreate('admin', 'web');
        $this->userA->assignRole($adminRole);

        // Attempt to grant an access scope in orgA for userB (who belongs to orgB)
        $response = $this->actingAs($this->userA)->post('/access-scopes', [
            'user_id' => $this->userB->id,
            'scope_type' => 'direction',
            'direction_id' => $this->dirA->id,
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('access_scopes', [
            'user_id' => $this->userB->id,
            'direction_id' => $this->dirA->id,
        ]);
    }

    public function test_cannot_create_document_type_with_direction_of_another_organization(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $adminRole = Role::findOrCreate('admin', 'web');
        $this->userA->assignRole($adminRole);

        $response = $this->actingAs($this->userA)->post('/document-types', [
            'name' => 'Cross-tenant DocType',
            'direction_id' => $this->dirB->id,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('folders', [
            'name' => 'Cross-tenant DocType',
        ]);
    }

    public function test_document_web_show_correctly_differentiates_view_and_download_permissions(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        $viewRole = Role::create(['name' => 'viewer_only', 'guard_name' => 'web']);
        $viewPerm = Permission::findOrCreate('documents.view', 'web');
        $viewRole->givePermissionTo($viewPerm);

        $this->userA->assignRole($viewRole);

        $doc = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => User::factory()->create(['organization_id' => $this->orgA->id])->id,
            'name' => 'Confidential Report',
        ]);

        $response = $this->actingAs($this->userA)->get("/documents/{$doc->id}");

        $response->assertOk();
        $permissions = $response->viewData('page')['props']['permissions'];

        $this->assertFalse($permissions['can_download'], 'User without documents.download must not have can_download capability');
        $this->assertFalse($permissions['can_archive'], 'User without documents.archive must not have can_archive capability');
    }

    public function test_tenant_cannot_download_or_preview_document_belonging_to_another_organization(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $adminRole = Role::findOrCreate('admin', 'web');
        $this->userB->assignRole($adminRole);

        $docA = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'name' => 'Org A Secret',
        ]);

        $versionA = DocumentVersion::factory()->create([
            'document_id' => $docA->id,
            'version_number' => 1,
            'storage_path' => 'documents/secret.pdf',
            'storage_disk' => 'private',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $this->userA->id,
        ]);
        Storage::disk('private')->put('documents/secret.pdf', 'CONFIDENTIAL CONTENT');

        // User B attempts to preview Doc A
        $previewResponse = $this->actingAs($this->userB)->get("/documents/{$docA->id}/preview");
        $this->assertTrue(in_array($previewResponse->status(), [403, 404], true));

        // User B attempts to download Doc A
        $downloadResponse = $this->actingAs($this->userB)->get("/documents/{$docA->id}/download");
        $this->assertTrue(in_array($downloadResponse->status(), [403, 404], true));
    }

    public function test_platform_admin_cannot_access_tenant_dashboard_without_tenant_auth(): void
    {
        $platformAdmin = PlatformUser::create([
            'name' => 'Platform Operator',
            'email' => 'operator@saas-platform.test',
            'password' => Hash::make('SecuredPlatformPass123!'),
            'role' => 'platform_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($platformAdmin, 'platform')->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_tenant_user_cannot_access_platform_admin_dashboard(): void
    {
        $response = $this->actingAs($this->userA, 'web')->get('/platform');
        $response->assertRedirect('/platform/login');
    }
}
