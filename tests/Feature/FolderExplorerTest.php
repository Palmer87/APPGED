<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FolderExplorerTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminUser;

    protected User $regularUser;

    protected Role $adminRole;

    protected Role $regularRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['name' => 'Org A']);
        $this->orgB = Organization::factory()->create(['name' => 'Org B']);

        $teamForeignKey = config('permission.column_names.team_foreign_key', 'organization_id');
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);

        $permissions = [
            'folders.view', 'folders.create', 'folders.update', 'folders.delete', 'folders.share',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgA->id,
        ]);
        $this->adminRole->syncPermissions($permissions);

        $this->regularRole = Role::firstOrCreate([
            'name' => 'utilisateur',
            'guard_name' => 'web',
            $teamForeignKey => $this->orgA->id,
        ]);

        $this->adminUser = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Admin',
            'last_name' => 'Explorer',
            'email' => 'admin@explorer.test',
        ]);
        $this->adminUser->assignRole($this->adminRole);

        $this->regularUser = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'first_name' => 'Regular',
            'last_name' => 'Worker',
            'email' => 'worker@explorer.test',
        ]);
        $this->regularUser->assignRole($this->regularRole);
    }

    public function test_guest_is_redirected_to_login_from_folders(): void
    {
        $response = $this->get('/folders');
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_view_folders(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/folders');
        $response->assertForbidden();
    }

    public function test_admin_can_view_root_folders_and_documents(): void
    {
        $rootFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Administration',
            'parent_id' => null,
        ]);

        $subFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'RH',
            'parent_id' => $rootFolder->id,
        ]);

        $rootDoc = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Document Racine',
            'folder_id' => null,
            'uploaded_by' => $this->adminUser->id,
            'status' => 'active',
        ]);

        $subDoc = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Document RH',
            'folder_id' => $subFolder->id,
            'uploaded_by' => $this->adminUser->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/folders');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Folders/Index')
            ->where('currentFolder', null)
            ->where('parentFolder', null)
            ->has('breadcrumbs', 1)
            ->where('breadcrumbs.0.name', 'Accueil')
            ->has('subfolders', 1)
            ->where('subfolders.0.name', 'Administration')
            ->has('documents', 1)
            ->where('documents.0.name', 'Document Racine')
            ->has('tree')
            ->has('can')
        );
    }

    public function test_admin_can_navigate_into_subfolder_with_breadcrumbs(): void
    {
        $rootFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Administration',
            'parent_id' => null,
        ]);

        $rhFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'RH',
            'parent_id' => $rootFolder->id,
        ]);

        $contratFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Contrats',
            'parent_id' => $rhFolder->id,
        ]);

        $docInContrat = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Contrat CDI.pdf',
            'folder_id' => $contratFolder->id,
            'uploaded_by' => $this->adminUser->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/folders/{$contratFolder->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Folders/Index')
            ->where('currentFolder.id', $contratFolder->id)
            ->where('parentFolder.id', $rhFolder->id)
            ->has('breadcrumbs', 4) // Accueil > Administration > RH > Contrats
            ->where('breadcrumbs.0.name', 'Accueil')
            ->where('breadcrumbs.1.name', 'Administration')
            ->where('breadcrumbs.2.name', 'RH')
            ->where('breadcrumbs.3.name', 'Contrats')
            ->has('documents', 1)
            ->where('documents.0.name', 'Contrat CDI.pdf')
        );
    }

    public function test_admin_can_create_root_and_subfolder(): void
    {
        // 1. Create root folder
        $response = $this->actingAs($this->adminUser)->post('/folders', [
            'name' => 'Projets 2026',
            'description' => 'Dossier racine pour tous les projets',
            'parent_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->orgA->id,
            'name' => 'Projets 2026',
            'parent_id' => null,
            'path' => 'Projets 2026',
        ]);

        $root = Folder::where('name', 'Projets 2026')->first();

        // 2. Create subfolder
        $responseSub = $this->actingAs($this->adminUser)->post('/folders', [
            'name' => 'Projet Alpha',
            'description' => 'Sous-projet Alpha',
            'parent_id' => $root->id,
        ]);

        $responseSub->assertRedirect();
        $this->assertDatabaseHas('folders', [
            'organization_id' => $this->orgA->id,
            'name' => 'Projet Alpha',
            'parent_id' => $root->id,
            'path' => 'Projets 2026/Projet Alpha',
        ]);
    }

    public function test_admin_can_move_folder_to_another_parent(): void
    {
        $folderA = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Dossier A',
            'parent_id' => null,
        ]);

        $folderB = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Dossier B',
            'parent_id' => null,
        ]);

        $subFolder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Sous-dossier',
            'parent_id' => $folderA->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put("/folders/{$subFolder->id}", [
            'name' => 'Sous-dossier',
            'parent_id' => $folderB->id,
        ]);

        $response->assertRedirect();
        $subFolder->refresh();
        $this->assertEquals($folderB->id, $subFolder->parent_id);
    }

    public function test_cannot_move_folder_into_itself_or_descendant(): void
    {
        $parent = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Parent',
            'parent_id' => null,
        ]);

        $child = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        // Attempt to move parent into child (creates cycle)
        $response = $this->actingAs($this->adminUser)->put("/folders/{$parent->id}", [
            'name' => 'Parent',
            'parent_id' => $child->id,
        ]);

        $response->assertSessionHas('error');
        $parent->refresh();
        $this->assertNull($parent->parent_id);
    }

    public function test_admin_can_move_document_between_folders_and_to_root(): void
    {
        $folder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Archive Folder',
        ]);

        $doc = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Rapport.pdf',
            'folder_id' => null,
            'uploaded_by' => $this->adminUser->id,
            'status' => 'active',
        ]);

        // 1. Move to folder
        $response = $this->actingAs($this->adminUser)->post("/documents/{$doc->id}/move", [
            'folder_id' => $folder->id,
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals($folder->id, $doc->folder_id);

        // 2. Move to root
        $responseRoot = $this->actingAs($this->adminUser)->post("/documents/{$doc->id}/move", [
            'folder_id' => null,
        ]);

        $responseRoot->assertRedirect();
        $doc->refresh();
        $this->assertNull($doc->folder_id);
    }

    public function test_multi_tenant_isolation_prevents_viewing_other_organization_folder(): void
    {
        $folderB = Folder::factory()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Confidentiel Org B',
        ]);

        $response = $this->actingAs($this->adminUser)->get("/folders/{$folderB->id}");
        $response->assertForbidden();
    }

    public function test_admin_can_delete_folder(): void
    {
        $folder = Folder::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Folder to delete',
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/folders/{$folder->id}");

        $response->assertRedirect('/folders');
        $this->assertSoftDeleted('folders', ['id' => $folder->id]);
    }
}
