<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workflow;
use App\Notifications\DocumentSharedNotification;
use App\Services\AccessControlService;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected AccessControlService $aclService;

    protected string $tokenA;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('local');

        $this->aclService = app(AccessControlService::class);

        $this->orgA = Organization::factory()->create(['name' => 'Acme Corporation']);
        $this->orgB = Organization::factory()->create(['name' => 'Beta Industries']);

        $this->userA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@acme.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->userB = User::factory()->create([
            'organization_id' => $this->orgB->id,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@beta.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $allPermissions = [
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download', 'documents.share', 'documents.archive', 'documents.restore',
            'folders.view', 'folders.create', 'folders.update', 'folders.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'tags.view', 'tags.create', 'tags.update', 'tags.delete',
            'metadata.view', 'metadata.create', 'metadata.update', 'metadata.delete',
            'workflows.view', 'workflows.create', 'workflows.update', 'workflows.execute', 'workflows.approve', 'workflows.reject', 'workflows.cancel',
            'comments.view', 'comments.create', 'comments.update', 'comments.delete',
            'organizations.view', 'audit.view',
        ];

        foreach ($allPermissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Setup Org A admin
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::findOrCreate('admin', 'web');
        $roleA->syncPermissions($allPermissions);
        $this->userA->assignRole($roleA);

        // Setup Org B admin
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::findOrCreate('admin', 'web');
        $roleB->syncPermissions($allPermissions);
        $this->userB->assignRole($roleB);

        // Create token for userA
        $this->tokenA = $this->userA->createToken('test-token')->plainTextToken;
    }

    protected function authHeaders(?string $token = null): array
    {
        return [
            'Authorization' => 'Bearer '.($token ?? $this->tokenA),
            'Accept' => 'application/json',
        ];
    }

    protected function createAccessibleDocument(User $user, array $attributes = []): Document
    {
        $doc = Document::factory()->create(array_merge([
            'organization_id' => $user->organization_id,
            'uploaded_by' => $user->id,
            'status' => 'active',
            'storage_disk' => 'private',
            'storage_path' => 'organizations/'.$user->organization_id.'/documents/test.pdf',
            'file_name' => 'test.pdf',
        ], $attributes));

        Storage::disk('private')->put($doc->storage_path, 'sample file content');

        $this->aclService->grantDocumentPermission($doc, $user, 'view');
        $this->aclService->grantDocumentPermission($doc, $user, 'update');
        $this->aclService->grantDocumentPermission($doc, $user, 'delete');
        $this->aclService->grantDocumentPermission($doc, $user, 'download');
        $this->aclService->grantDocumentPermission($doc, $user, 'share');

        return $doc;
    }

    public function test_user_can_login_with_valid_credentials_and_receives_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@acme.test',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'organization_id',
                        'organization' => ['id', 'name'],
                        'roles',
                        'permissions',
                    ],
                ],
            ]);

        $this->assertEquals('Bearer', $response->json('data.token_type'));
        $this->assertArrayNotHasKey('password', $response->json('data.user'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data.user'));
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@acme.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'email' => 'inactive@acme.test',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@acme.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_access_me_endpoint_with_bearer_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->userA->id)
            ->assertJsonPath('data.email', $this->userA->email)
            ->assertJsonPath('data.organization.id', $this->orgA->id);
    }

    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me', ['Accept' => 'application/json']);

        $response->assertStatus(401);
    }

    public function test_user_can_logout_and_revoke_current_token(): void
    {
        $this->assertEquals(1, PersonalAccessToken::count());
        $response = $this->postJson('/api/v1/auth/logout', [], $this->authHeaders());
        $response->assertStatus(200);

        $this->assertEquals(0, PersonalAccessToken::count());

        $this->flushSession();
        $this->app['auth']->forgetGuards();

        // Subsequent call with same token must fail
        $retry = $this->getJson('/api/v1/auth/me', $this->authHeaders());
        $retry->assertStatus(401);
    }

    public function test_user_can_logout_all_and_revoke_all_tokens(): void
    {
        $token2 = $this->userA->createToken('device-2')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/logout-all', [], $this->authHeaders($token2));
        $response->assertStatus(200);

        $this->flushSession();
        $this->app['auth']->forgetGuards();

        // Both tokens should now be invalid
        $this->getJson('/api/v1/auth/me', $this->authHeaders())->assertStatus(401);
        $this->getJson('/api/v1/auth/me', $this->authHeaders($token2))->assertStatus(401);
    }

    public function test_tenant_isolation_is_enforced_across_api_endpoints(): void
    {
        $docB = $this->createAccessibleDocument($this->userB, ['name' => 'Secret B']);

        // User A attempts to view Document B belonging to Org B
        $response = $this->getJson("/api/v1/documents/{$docB->id}", $this->authHeaders());

        // Must be rejected with 403 Forbidden
        $response->assertStatus(403);
    }

    public function test_documents_crud_and_upload_via_api(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 200, 'application/pdf');

        // 1. Upload document
        $uploadResponse = $this->postJson('/api/v1/documents', [
            'name' => 'Contract 2026',
            'description' => 'Important contract',
            'file' => $file,
        ], $this->authHeaders());

        $uploadResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Contract 2026')
            ->assertJsonPath('data.organization_id', $this->orgA->id);

        $docId = $uploadResponse->json('data.id');

        // 2. List documents
        $listResponse = $this->getJson('/api/v1/documents', $this->authHeaders());
        $listResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Contract 2026']);

        // 3. Show document
        $showResponse = $this->getJson("/api/v1/documents/{$docId}", $this->authHeaders());
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $docId);

        // 4. Update document
        $updateResponse = $this->putJson("/api/v1/documents/{$docId}", [
            'name' => 'Contract 2026 Updated',
        ], $this->authHeaders());

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Contract 2026 Updated');

        // 5. Delete document (move to trash)
        $deleteResponse = $this->deleteJson("/api/v1/documents/{$docId}", [], $this->authHeaders());
        $deleteResponse->assertStatus(200);

        $this->assertSoftDeleted('documents', ['id' => $docId]);
    }

    public function test_document_versions_via_api(): void
    {
        $file1 = UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf');
        $doc = app(DocumentService::class)->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'name' => 'Versioned Document',
        ], $file1);
        $this->aclService->grantDocumentPermission($doc, $this->userA, 'view');
        $this->aclService->grantDocumentPermission($doc, $this->userA, 'update');

        // Upload version 2
        $file2 = UploadedFile::fake()->create('v2.pdf', 150, 'application/pdf');
        $v2Response = $this->postJson("/api/v1/documents/{$doc->id}/versions", [
            'file' => $file2,
            'comment' => 'Second revision',
        ], $this->authHeaders());

        $v2Response->assertStatus(201)
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.comment', 'Second revision');

        // List versions
        $listVersions = $this->getJson("/api/v1/documents/{$doc->id}/versions", $this->authHeaders());
        $listVersions->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_folders_api(): void
    {
        // 1. Create folder
        $createResponse = $this->postJson('/api/v1/folders', [
            'name' => 'Finance',
            'description' => 'Financial records',
        ], $this->authHeaders());

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Finance')
            ->assertJsonPath('data.organization_id', $this->orgA->id);

        $folderId = $createResponse->json('data.id');

        // 2. List folders
        $listResponse = $this->getJson('/api/v1/folders', $this->authHeaders());
        $listResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Finance']);

        // 3. Delete folder
        $deleteResponse = $this->deleteJson("/api/v1/folders/{$folderId}", [], $this->authHeaders());
        $deleteResponse->assertStatus(200);

        $this->assertSoftDeleted('folders', ['id' => $folderId]);
    }

    public function test_categories_and_tags_api(): void
    {
        // Category
        $catResponse = $this->postJson('/api/v1/categories', [
            'name' => 'Invoices',
            'description' => 'Client and vendor invoices',
        ], $this->authHeaders());

        $catResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Invoices');

        $catId = $catResponse->json('data.id');

        $this->getJson('/api/v1/categories', $this->authHeaders())
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Invoices']);

        // Tag
        $tagResponse = $this->postJson('/api/v1/tags', [
            'name' => 'Urgent',
            'description' => 'Urgent processing needed',
        ], $this->authHeaders());

        $tagResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Urgent');

        $this->getJson('/api/v1/tags', $this->authHeaders())
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Urgent']);
    }

    public function test_custom_metadata_api(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, ['name' => 'Invoice #101']);

        // Create definition
        $defResponse = $this->postJson('/api/v1/metadata/definitions', [
            'name' => 'Invoice Number',
            'key' => 'invoice_number',
            'type' => 'string',
        ], $this->authHeaders());

        $defResponse->assertStatus(201)
            ->assertJsonPath('data.key', 'invoice_number');

        // Set metadata on document
        $setMeta = $this->putJson("/api/v1/metadata/documents/{$doc->id}", [
            'values' => [
                'invoice_number' => 'INV-2026-001',
            ],
        ], $this->authHeaders());

        $setMeta->assertStatus(200);

        // Get metadata on document
        $getMeta = $this->getJson("/api/v1/metadata/documents/{$doc->id}", $this->authHeaders());
        $getMeta->assertStatus(200)
            ->assertJsonFragment(['key' => 'invoice_number', 'value' => 'INV-2026-001']);
    }

    public function test_favorites_and_recent_api(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, ['name' => 'Report 2026']);

        // 1. Toggle favorite (add)
        $favAdd = $this->postJson("/api/v1/favorites/documents/{$doc->id}", [], $this->authHeaders());
        $favAdd->assertStatus(200)
            ->assertJsonPath('data.is_favorite', true);

        // 2. List favorites
        $favList = $this->getJson('/api/v1/favorites', $this->authHeaders());
        $favList->assertStatus(200)
            ->assertJsonFragment(['name' => 'Report 2026']);

        // 3. Toggle favorite (remove)
        $favRemove = $this->postJson("/api/v1/favorites/documents/{$doc->id}", [], $this->authHeaders());
        $favRemove->assertStatus(200)
            ->assertJsonPath('data.is_favorite', false);

        // 4. Recent documents
        $recentList = $this->getJson('/api/v1/recent', $this->authHeaders());
        $recentList->assertStatus(200);
    }

    public function test_document_shares_api(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, ['name' => 'Shared Doc']);
        $colleague = User::factory()->create(['organization_id' => $this->orgA->id]);

        // Share with colleague
        $shareResponse = $this->postJson("/api/v1/shares/documents/{$doc->id}/user", [
            'user_id' => $colleague->id,
            'permission' => 'view',
        ], $this->authHeaders());

        $shareResponse->assertStatus(201)
            ->assertJsonPath('data.user_id', $colleague->id);

        $shareId = $shareResponse->json('data.id');

        // Revoke share
        $revokeResponse = $this->deleteJson("/api/v1/shares/documents/{$doc->id}/{$shareId}", [], $this->authHeaders());
        $revokeResponse->assertStatus(200);
    }

    public function test_workflows_api(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, ['name' => 'Validation Target']);

        // 1. Create workflow
        $createWorkflow = $this->postJson('/api/v1/workflows', [
            'name' => 'Purchase Approval',
            'steps' => [
                [
                    'name' => 'Manager Step',
                    'approver_type' => 'user',
                    'approver_id' => $this->userA->id,
                ],
            ],
        ], $this->authHeaders());

        $createWorkflow->assertStatus(201)
            ->assertJsonPath('data.name', 'Purchase Approval');

        $workflowId = $createWorkflow->json('data.id');

        // 2. Start workflow instance
        $startResponse = $this->postJson("/api/v1/workflows/documents/{$doc->id}/start/{$workflowId}", [], $this->authHeaders());
        $startResponse->assertStatus(201);

        $instanceId = $startResponse->json('data.id');

        // 3. Approve step
        $approveResponse = $this->postJson("/api/v1/workflows/instances/{$instanceId}/approve", [
            'comment' => 'Looks good to me',
        ], $this->authHeaders());

        $approveResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_document_comments_api(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, ['name' => 'Discussion Doc']);

        // 1. Create root comment
        $commentResponse = $this->postJson("/api/v1/comments/documents/{$doc->id}", [
            'content' => 'First review note',
        ], $this->authHeaders());

        $commentResponse->assertStatus(201)
            ->assertJsonPath('data.content', 'First review note');

        $commentId = $commentResponse->json('data.id');

        // 2. Reply to comment
        $replyResponse = $this->postJson("/api/v1/comments/{$commentId}/reply", [
            'content' => 'I agree with note',
        ], $this->authHeaders());

        $replyResponse->assertStatus(201)
            ->assertJsonPath('data.content', 'I agree with note');

        // 3. List comments
        $listComments = $this->getJson("/api/v1/comments/documents/{$doc->id}", $this->authHeaders());
        $listComments->assertStatus(200)
            ->assertJsonFragment(['content' => 'First review note']);
    }

    public function test_notifications_api(): void
    {
        $doc = $this->createAccessibleDocument($this->userA, ['name' => 'Notif Doc']);
        $this->userA->notify(new DocumentSharedNotification($doc, $this->userA, 'view'));

        // 1. List notifications
        $listResponse = $this->getJson('/api/v1/notifications', $this->authHeaders());
        $listResponse->assertStatus(200)
            ->assertJsonPath('meta.unread_count', 1);

        $notifId = $listResponse->json('data.0.id');

        // 2. Mark as read
        $readResponse = $this->postJson("/api/v1/notifications/{$notifId}/read", [], $this->authHeaders());
        $readResponse->assertStatus(200)
            ->assertJsonPath('unread_count', 0);

        // 3. Mark all as read
        $readAllResponse = $this->postJson('/api/v1/notifications/read-all', [], $this->authHeaders());
        $readAllResponse->assertStatus(200)
            ->assertJsonPath('unread_count', 0);
    }

    public function test_search_api(): void
    {
        $this->createAccessibleDocument($this->userA, ['name' => 'Budget 2026']);
        $this->createAccessibleDocument($this->userA, ['name' => 'Marketing Plan']);

        $searchResponse = $this->getJson('/api/v1/search?q=Budget', $this->authHeaders());

        $searchResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Budget 2026']);
    }
}
