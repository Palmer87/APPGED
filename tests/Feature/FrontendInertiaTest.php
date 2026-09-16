<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Folder;
use App\Models\MetadataDefinition;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FrontendInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $user;

    protected User $adminUser;

    protected AccessControlService $aclService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        $this->aclService = app(AccessControlService::class);

        $this->org = Organization::factory()->create(['name' => 'Acme Corp']);

        // Set team ID for permissions
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);

        $roleUser = Role::firstOrCreate(['name' => 'utilisateur', 'guard_name' => 'web', 'organization_id' => $this->org->id]);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', 'organization_id' => $this->org->id]);

        $permissions = [
            'folders.view', 'folders.create', 'folders.update', 'folders.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'tags.view', 'tags.create', 'tags.update', 'tags.delete',
            'metadata.view', 'metadata.create', 'metadata.update', 'metadata.delete',
            'workflows.view', 'workflows.create',
        ];

        foreach ($permissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $roleAdmin->givePermissionTo($perm);
            if (in_array($permName, ['folders.view', 'documents.view', 'documents.create', 'categories.view', 'tags.view', 'metadata.view', 'workflows.view'], true)) {
                $roleUser->givePermissionTo($perm);
            }
        }

        $this->user = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@acme.test',
        ]);
        $this->user->assignRole($roleUser);

        $this->adminUser = User::factory()->create([
            'organization_id' => $this->org->id,
            'first_name' => 'Alice',
            'last_name' => 'Admin',
            'email' => 'alice@acme.test',
        ]);
        $this->adminUser->assignRole($roleAdmin);
    }

    protected function createAccessibleDocument(User $user, array $attributes = []): Document
    {
        $doc = Document::factory()->create(array_merge([
            'organization_id' => $user->organization_id,
            'uploaded_by' => $user->id,
            'status' => 'active',
        ], $attributes));

        $this->aclService->grantDocumentPermission($doc, $user, 'view');

        return $doc;
    }

    public function test_guest_cannot_access_documents_index(): void
    {
        $response = $this->get('/documents');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_documents_index(): void
    {
        $this->createAccessibleDocument($this->user, [
            'name' => 'Contrat_Test.pdf',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/documents');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->has('documents.data', 1)
            ->where('documents.data.0.name', 'Contrat_Test.pdf')
            ->has('folders')
            ->has('categories')
            ->has('tags')
        );
    }

    public function test_authenticated_user_can_view_document_show(): void
    {
        $doc = Document::factory()->create([
            'organization_id' => $this->org->id,
            'uploaded_by' => $this->user->id,
            'name' => 'Facture_2026.pdf',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/documents/{$doc->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Show')
            ->where('document.id', $doc->id)
            ->where('document.name', 'Facture_2026.pdf')
            ->has('permissions')
            ->has('previewUrl')
        );
    }

    public function test_authenticated_user_can_upload_document_via_web(): void
    {
        $file = UploadedFile::fake()->create('nouveau_rapport.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->post('/documents', [
                'file' => $file,
                'name' => 'Nouveau Rapport 2026',
                'description' => 'Document de synthèse annuelle',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'organization_id' => $this->org->id,
            'name' => 'Nouveau Rapport 2026',
        ]);
    }

    public function test_authenticated_user_can_view_folders_index(): void
    {
        Folder::factory()->create([
            'organization_id' => $this->org->id,
            'created_by' => $this->user->id,
            'name' => 'Dossier RH',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/folders');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Folders/Index')
            ->has('folders', 1)
            ->where('folders.0.name', 'Dossier RH')
        );
    }

    public function test_admin_can_create_folder_via_web(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post('/folders', [
                'name' => 'Comptabilité 2026',
                'description' => 'Dossier financier',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->org->id,
            'name' => 'Comptabilité 2026',
        ]);
    }

    public function test_authenticated_user_can_access_search_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/search?q=test');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Search/Index')
            ->has('folders')
            ->has('categories')
            ->has('tags')
        );
    }

    public function test_authenticated_user_can_view_favorites_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/favorites');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Favorites/Index')
            ->has('favorites')
        );
    }

    public function test_authenticated_user_can_view_recent_documents_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/recent');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Recent/Index')
            ->has('recentDocuments')
        );
    }

    public function test_authenticated_user_can_view_shares_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/shares');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Shares/Index')
            ->has('receivedShares')
            ->has('sentShares')
        );
    }

    public function test_authenticated_user_can_view_workflows_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/workflows');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Workflows/Index')
            ->has('workflows')
            ->has('instances')
        );
    }

    public function test_authenticated_user_can_view_notifications_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications')
            ->has('unread_count')
        );
    }

    public function test_authenticated_user_can_view_trash_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/documents/trash');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Trash/Index')
            ->has('documents')
        );
    }

    public function test_authenticated_user_can_view_archive_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/documents/archived');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Archive/Index')
            ->has('documents')
        );
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Index')
            ->where('user.email', 'jean@acme.test')
            ->has('preferences')
            ->has('supportedTypes')
        );
    }

    public function test_authenticated_user_can_update_profile_information(): void
    {
        $response = $this->actingAs($this->user)
            ->put('/profile', [
                'first_name' => 'Jean-Michel',
                'last_name' => 'Dupont',
                'phone' => '+33611223344',
                'job_title' => 'Chef de projet GED',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Jean-Michel',
            'job_title' => 'Chef de projet GED',
        ]);
    }

    public function test_admin_can_view_and_manage_categories_page(): void
    {
        Category::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Contrats',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/categories');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Categories/Index')
            ->has('categories', 1)
        );
    }

    public function test_admin_can_view_and_manage_tags_page(): void
    {
        Tag::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Urgent',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/tags');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tags/Index')
            ->has('tags', 1)
        );
    }

    public function test_admin_can_view_and_manage_metadata_page(): void
    {
        MetadataDefinition::factory()->create([
            'organization_id' => $this->org->id,
            'name' => 'Montant Contrat',
            'key' => 'montant_contrat',
            'type' => 'decimal',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/metadata');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Metadata/Index')
            ->has('definitions', 1)
            ->has('allowedTypes')
        );
    }
}
