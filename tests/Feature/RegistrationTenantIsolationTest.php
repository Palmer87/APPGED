<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use App\Services\RegistrationService;
use Database\Seeders\BillingPermissionSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrationTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            PlanSeeder::class,
            BillingPermissionSeeder::class,
        ]);
    }

    public function test_two_registered_organizations_are_strictly_isolated(): void
    {
        $service = app(RegistrationService::class);

        // Register Tenant Alpha
        $alphaResult = $service->register(
            orgData: ['name' => 'Tenant Alpha', 'city' => 'Abidjan'],
            adminData: [
                'first_name' => 'Alice',
                'last_name' => 'Alpha',
                'email' => 'alice@alpha.test',
                'password' => 'secret1234',
            ],
            planSlug: 'professional',
            billingCycle: 'monthly'
        );

        // Register Tenant Beta
        $betaResult = $service->register(
            orgData: ['name' => 'Tenant Beta', 'city' => 'Dakar'],
            adminData: [
                'first_name' => 'Bob',
                'last_name' => 'Beta',
                'email' => 'bob@beta.test',
                'password' => 'secret1234',
            ],
            planSlug: 'essential',
            billingCycle: 'annual'
        );

        $orgAlpha = $alphaResult['organization'];
        $userAlpha = $alphaResult['user'];
        $subAlpha = $alphaResult['subscription'];

        $orgBeta = $betaResult['organization'];
        $userBeta = $betaResult['user'];
        $subBeta = $betaResult['subscription'];

        // Assert different IDs
        $this->assertNotSame($orgAlpha->id, $orgBeta->id);
        $this->assertNotSame($userAlpha->id, $userBeta->id);
        $this->assertSame($orgAlpha->id, $userAlpha->organization_id);
        $this->assertSame($orgBeta->id, $userBeta->organization_id);

        // Subscriptions are strictly isolated
        $this->assertSame($orgAlpha->id, $subAlpha->organization_id);
        $this->assertSame($orgBeta->id, $subBeta->organization_id);
        $this->assertSame('professional', $subAlpha->plan->slug);
        $this->assertSame('essential', $subBeta->plan->slug);

        // User list isolation check
        $this->actingAs($userAlpha);
        app(PermissionRegistrar::class)->setPermissionsTeamId($orgAlpha->id);

        $response = $this->get('/users');
        $response->assertOk();

        // Alice cannot see Bob in users listing
        $response->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'alice@alpha.test')
        );

        // Cross-tenant User editing must be forbidden
        $forbiddenResponse = $this->get("/users/{$userBeta->id}/edit");
        $forbiddenResponse->assertForbidden();

        // Document isolation
        $folderAlpha = Folder::create([
            'organization_id' => $orgAlpha->id,
            'name' => 'Dossier Alpha',
            'created_by' => $userAlpha->id,
        ]);

        $folderBeta = Folder::create([
            'organization_id' => $orgBeta->id,
            'name' => 'Dossier Beta',
            'created_by' => $userBeta->id,
        ]);

        $docBeta = Document::create([
            'organization_id' => $orgBeta->id,
            'folder_id' => $folderBeta->id,
            'uploaded_by' => $userBeta->id,
            'name' => 'Secret Beta Document',
            'file_name' => 'secret_beta.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_path' => 'documents/beta/secret.pdf',
            'status' => 'active',
        ]);

        // Alice cannot access Bob's document
        $docResponse = $this->get("/documents/{$docBeta->id}");
        $this->assertTrue(in_array($docResponse->status(), [403, 404], true));
    }
}
