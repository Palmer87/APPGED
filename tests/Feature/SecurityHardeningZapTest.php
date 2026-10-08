<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecurityHardeningZapTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected Document $docA;

    protected Document $docB;

    protected DocumentVersion $versionA;

    protected DocumentVersion $versionB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->seed(PlanSeeder::class);

        $this->orgA = Organization::factory()->create(['name' => 'Org Alpha', 'status' => 'active']);
        $this->orgB = Organization::factory()->create(['name' => 'Org Beta', 'status' => 'active']);

        $this->seed(RolePermissionSeeder::class);

        $this->userA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'alice@alpha.test',
            'status' => 'active',
        ]);

        $this->userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'email' => 'bob@beta.test',
            'status' => 'active',
        ]);

        // Assign admin role to userA in orgA
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $adminRoleA = Role::findOrCreate('admin', 'web');
        $this->userA->assignRole($adminRoleA);

        // Assign admin role to userB in orgB
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $adminRoleB = Role::findOrCreate('admin', 'web');
        $this->userB->assignRole($adminRoleB);

        // Create active subscription for orgA
        $plan = \App\Models\Plan::first();
        if ($plan) {
            \App\Models\Subscription::create([
                'organization_id' => $this->orgA->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
            ]);
        }

        // Create Doc A in Org A
        $this->docA = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'name' => 'Document Alpha',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'storage_disk' => 'private',
            'storage_path' => 'organizations/'.$this->orgA->id.'/documents/1/versions/1/docA.pdf',
        ]);
        $this->versionA = DocumentVersion::factory()->create([
            'document_id' => $this->docA->id,
            'version_number' => 1,
            'file_name' => 'docA.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 2048,
            'storage_disk' => 'private',
            'storage_path' => 'organizations/'.$this->orgA->id.'/documents/1/versions/1/docA.pdf',
            'uploaded_by' => $this->userA->id,
        ]);
        Storage::disk('private')->put($this->versionA->storage_path, '%PDF-1.4 confidential content alpha');

        // Create Doc B in Org B
        $this->docB = Document::factory()->create([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
            'name' => 'Document Beta',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'storage_disk' => 'private',
            'storage_path' => 'organizations/'.$this->orgB->id.'/documents/2/versions/1/docB.pdf',
        ]);
        $this->versionB = DocumentVersion::factory()->create([
            'document_id' => $this->docB->id,
            'version_number' => 1,
            'file_name' => 'docB.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 4096,
            'storage_disk' => 'private',
            'storage_path' => 'organizations/'.$this->orgB->id.'/documents/2/versions/1/docB.pdf',
            'uploaded_by' => $this->userB->id,
        ]);
        Storage::disk('private')->put($this->versionB->storage_path, '%PDF-1.4 confidential content beta');
    }

    /**
     * 1. Test presence of mandatory OWASP security headers on web routes.
     */
    public function test_security_headers_are_present_on_web_routes(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        // X-Content-Type-Options: nosniff
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: SAMEORIGIN
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        // Referrer-Policy
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy
        $this->assertTrue($response->headers->has('Permissions-Policy'));
        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));

        // Content-Security-Policy
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com", $csp);
        $this->assertStringContainsString("font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("frame-src 'self' blob:", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    /**
     * 2. Test HSTS header enforcement over HTTPS.
     */
    public function test_hsts_header_is_enforced_over_https(): void
    {
        $response = $this->get('https://localhost/');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    /**
     * 3. Test security headers on API endpoints.
     */
    public function test_security_headers_are_present_on_api_routes(): void
    {
        $response = $this->getJson('/api/v1/plans');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    /**
     * 4. Test cache-control hardening on authenticated routes.
     */
    public function test_cache_control_hardening_on_authenticated_routes(): void
    {
        $response = $this->actingAs($this->userA)->get('/dashboard');

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    /**
     * 5. Test session cookie configuration.
     */
    public function test_session_cookie_attributes_are_securely_configured(): void
    {
        $this->assertTrue(config('session.http_only'), 'Session cookie must have HttpOnly enabled.');
        $this->assertSame('lax', config('session.same_site'), 'Session cookie SameSite must be lax.');
    }

    /**
     * 6. Multi-tenant IDOR: User A cannot view Document B.
     */
    public function test_tenant_user_a_cannot_view_document_b(): void
    {
        $response = $this->actingAs($this->userA)->get("/documents/{$this->docB->id}");
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * 7. Multi-tenant IDOR: User A cannot download Document B.
     */
    public function test_tenant_user_a_cannot_download_document_b(): void
    {
        $response = $this->actingAs($this->userA)->get("/documents/{$this->docB->id}/download");
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * 8. Multi-tenant IDOR: User A cannot preview Document B.
     */
    public function test_tenant_user_a_cannot_preview_document_b(): void
    {
        $response = $this->actingAs($this->userA)->get("/documents/{$this->docB->id}/preview");
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * 9. Multi-tenant IDOR: User A cannot download Document B specific version.
     */
    public function test_tenant_user_a_cannot_download_document_b_version(): void
    {
        $response = $this->actingAs($this->userA)->get("/documents/{$this->docB->id}/versions/{$this->versionB->id}/download");
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * 10. Multi-tenant IDOR: User A cannot preview Document B specific version.
     */
    public function test_tenant_user_a_cannot_preview_document_b_version(): void
    {
        $response = $this->actingAs($this->userA)->get("/documents/{$this->docB->id}/versions/{$this->versionB->id}/preview");
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * 11. Multi-tenant IDOR: User B cannot download or preview Document A.
     */
    public function test_tenant_user_b_cannot_access_document_a(): void
    {
        $responseDownload = $this->actingAs($this->userB)->get("/documents/{$this->docA->id}/download");
        $this->assertTrue(in_array($responseDownload->status(), [403, 404], true));

        $responsePreview = $this->actingAs($this->userB)->get("/documents/{$this->docA->id}/preview");
        $this->assertTrue(in_array($responsePreview->status(), [403, 404], true));
    }

    /**
     * 12. Multi-tenant IDOR via API endpoints.
     */
    public function test_tenant_user_a_cannot_access_document_b_via_api(): void
    {
        $token = $this->userA->createToken('test-token')->plainTextToken;

        // API get document B
        $responseShow = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/documents/{$this->docB->id}");
        $this->assertTrue(in_array($responseShow->status(), [403, 404], true));

        // API download document B
        $responseDownload = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/documents/{$this->docB->id}/download");
        $this->assertTrue(in_array($responseDownload->status(), [403, 404], true));

        // API preview document B
        $responsePreview = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/documents/{$this->docB->id}/preview");
        $this->assertTrue(in_array($responsePreview->status(), [403, 404], true));
    }

    /**
     * 13. Upload hardening: dangerous extensions & path traversal rejected.
     */
    public function test_upload_blocks_dangerous_extensions_and_path_traversal(): void
    {
        $token = $this->userA->createToken('upload-test-token')->plainTextToken;

        // 1. Double extension with dangerous PHP extension
        $fakePhp = UploadedFile::fake()->create('malicious.php.pdf', 100, 'application/pdf');
        $resPhp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/documents', ['file' => $fakePhp, 'name' => 'Exploit File'], ['Accept' => 'application/json']);
        $this->assertSame(422, $resPhp->status());

        // 2. Null byte in file name
        $fakeNullByte = UploadedFile::fake()->create("exploit\0.pdf", 100, 'application/pdf');
        $resNullByte = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/documents', ['file' => $fakeNullByte, 'name' => 'Null Byte Attempt'], ['Accept' => 'application/json']);
        $this->assertSame(422, $resNullByte->status());

        // 3. Executable / script MIME type
        $fakeScript = UploadedFile::fake()->create('script.pdf', 100, 'text/x-php');
        $resScript = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/documents', ['file' => $fakeScript, 'name' => 'Script Mime'], ['Accept' => 'application/json']);
        $this->assertSame(422, $resScript->status());

        // 4. Unauthorized executable extension
        $fakeExe = UploadedFile::fake()->create('program.exe', 100, 'application/x-msdownload');
        $resExe = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/documents', ['file' => $fakeExe, 'name' => 'Exe Program'], ['Accept' => 'application/json']);
        $this->assertSame(422, $resExe->status());
    }

    /**
     * 14. Documents are stored on private storage and never exposed via public URLs.
     */
    public function test_documents_are_stored_privately_and_never_public(): void
    {
        $this->assertSame('private', $this->docA->storage_disk);
        $this->assertFalse(Storage::disk('public')->exists($this->docA->storage_path));
        $this->assertTrue(Storage::disk('private')->exists($this->docA->storage_path));
    }

    /**
     * 15. Platform Admin boundary: Tenant user cannot access platform admin.
     */
    public function test_tenant_user_cannot_access_platform_admin(): void
    {
        $response = $this->actingAs($this->userA, 'web')->get('/platform');
        $response->assertRedirect('/platform/login');

        $responseOrg = $this->actingAs($this->userA, 'web')->get('/platform/organizations');
        $responseOrg->assertRedirect('/platform/login');
    }

    /**
     * 16. Platform Admin boundary: Platform user cannot access tenant dashboard directly.
     */
    public function test_platform_user_cannot_access_tenant_dashboard(): void
    {
        $platformAdmin = PlatformUser::create([
            'name' => 'Platform Super Admin',
            'email' => 'superadmin@gedapp.internal',
            'password' => Hash::make('StrongPassword123!'),
            'role' => 'platform_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($platformAdmin, 'platform')->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * 17. API Sanctum authentication boundary: unauthenticated requests return 401 without leakage.
     */
    public function test_unauthenticated_api_request_returns_401_json(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
        $this->assertFalse($response->headers->has('X-Debug-Token'));
    }
}
