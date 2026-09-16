<?php

namespace Tests\Feature;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowStatus;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Notifications\DocumentCommentedNotification;
use App\Services\DocumentCommentService;
use App\Services\DocumentLifecycleService;
use App\Services\WorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentCommentTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentCommentService $commentService;

    protected DocumentLifecycleService $lifecycleService;

    protected WorkflowService $workflowService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $managerA;

    protected User $userA1;

    protected User $userA2;

    protected User $userB1;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        config(['filesystems.default' => 'private']);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->seed(RolePermissionSeeder::class);

        $this->commentService = app(DocumentCommentService::class);
        $this->lifecycleService = app(DocumentLifecycleService::class);
        $this->workflowService = app(WorkflowService::class);

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->managerA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA1 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA2 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB1 = User::factory()->create(['organization_id' => $this->orgB->id]);

        // Assign Spatie roles in OrgA
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $this->adminA->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        $this->managerA->assignRole(Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']));
        $this->userA1->assignRole(Role::firstOrCreate(['name' => 'utilisateur', 'guard_name' => 'web']));
        $this->userA2->assignRole(Role::firstOrCreate(['name' => 'utilisateur', 'guard_name' => 'web']));

        // Assign Spatie roles in OrgB
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $this->userB1->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    protected function createDocument(array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->adminA->id;

        $doc = Document::create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'name' => 'Contrat.pdf',
            'file_name' => 'contrat.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat.pdf',
            'status' => 'active',
        ], $attributes));

        DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'file_name' => 'contrat.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat.pdf',
            'uploaded_by' => $uploaderId,
            'comment' => 'Initial version',
        ]);

        return $doc;
    }

    // ==========================================
    // 1. CRÉATION DE COMMENTAIRES
    // ==========================================

    public function test_user_with_access_can_create_root_document_comment(): void
    {
        $doc = $this->createDocument();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Premier commentaire sur le document.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_comments', [
            'document_id' => $doc->id,
            'user_id' => $this->userA1->id,
            'organization_id' => $this->orgA->id,
            'content' => 'Premier commentaire sur le document.',
            'parent_id' => null,
            'document_version_id' => null,
        ]);
    }

    public function test_user_can_create_comment_on_specific_document_version(): void
    {
        $doc = $this->createDocument();
        $version = $doc->versions()->first();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/versions/{$version->id}/comments", [
            'content' => 'Commentaire spécifique sur la v1.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_comments', [
            'document_id' => $doc->id,
            'document_version_id' => $version->id,
            'content' => 'Commentaire spécifique sur la v1.',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_comment(): void
    {
        $doc = $this->createDocument();

        $response = $this->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Non authentifié',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_from_another_organization_cannot_create_comment(): void
    {
        $doc = $this->createDocument();

        $response = $this->actingAs($this->userB1)->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Commentaire cross-tenant',
        ]);

        $response->assertForbidden();
    }

    public function test_comment_on_version_from_another_document_is_rejected(): void
    {
        $doc1 = $this->createDocument();
        $doc2 = $this->createDocument();
        $version2 = $doc2->versions()->first();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc1->id}/versions/{$version2->id}/comments", [
            'content' => 'Version incohérente',
        ]);

        $response->assertStatus(422);
    }

    public function test_comment_on_version_from_another_organization_is_rejected(): void
    {
        $docA = $this->createDocument();
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB1->id]);
        $versionB = $docB->versions()->first();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$docA->id}/versions/{$versionB->id}/comments", [
            'content' => 'Version cross-tenant',
        ]);

        $response->assertStatus(422);
    }

    // ==========================================
    // 2. CONTENU & SANITATION
    // ==========================================

    public function test_empty_comment_content_is_rejected(): void
    {
        $doc = $this->createDocument();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_whitespace_only_comment_content_is_rejected(): void
    {
        $doc = $this->createDocument();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => '     ',
        ]);

        $response->assertStatus(422);
    }

    public function test_comment_content_exceeding_10000_chars_is_rejected(): void
    {
        $doc = $this->createDocument();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => str_repeat('a', 10001),
        ]);

        $response->assertStatus(422);
    }

    public function test_html_tags_are_stripped_from_content(): void
    {
        $doc = $this->createDocument();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => '<script>alert("xss")</script><b>Texte propre</b>',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_comments', [
            'document_id' => $doc->id,
            'content' => 'alert("xss")Texte propre',
        ]);
    }

    // ==========================================
    // 3. MODIFICATION DE COMMENTAIRES
    // ==========================================

    public function test_author_can_update_their_own_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Contenu initial',
        ]);

        $response = $this->actingAs($this->userA1)->putJson("/comments/{$comment->id}", [
            'content' => 'Contenu modifié par l\'auteur',
        ]);

        $response->assertOk();
        $this->assertSame('Contenu modifié par l\'auteur', $comment->fresh()->content);
    }

    public function test_other_regular_user_cannot_update_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Texte original',
        ]);

        $response = $this->actingAs($this->userA2)->putJson("/comments/{$comment->id}", [
            'content' => 'Modification pirate',
        ]);

        $response->assertForbidden();
    }

    public function test_moderator_can_update_other_users_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Commentaire inapproprié',
        ]);

        $response = $this->actingAs($this->managerA)->putJson("/comments/{$comment->id}", [
            'content' => 'Commentaire modéré par le manager',
        ]);

        $response->assertOk();
        $this->assertSame('Commentaire modéré par le manager', $comment->fresh()->content);
    }

    public function test_cannot_update_comment_from_another_tenant(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB1->id]);
        $commentB = DocumentComment::factory()->forDocument($docB)->create([
            'user_id' => $this->userB1->id,
            'content' => 'Commentaire tenant B',
        ]);

        $response = $this->actingAs($this->adminA)->putJson("/comments/{$commentB->id}", [
            'content' => 'Modification cross-tenant',
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_update_comment_on_archived_document(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Commentaire actif',
        ]);

        $doc->update(['status' => 'archived']);

        $response = $this->actingAs($this->userA1)->putJson("/comments/{$comment->id}", [
            'content' => 'Modification sur archive',
        ]);

        $response->assertStatus(422);
    }

    // ==========================================
    // 4. SUPPRESSION & SOFTDELETES
    // ==========================================

    public function test_author_can_delete_their_own_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
        ]);

        $response = $this->actingAs($this->userA1)->deleteJson("/comments/{$comment->id}");

        $response->assertOk();
        $this->assertSoftDeleted('document_comments', ['id' => $comment->id]);
    }

    public function test_regular_user_cannot_delete_other_users_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
        ]);

        $response = $this->actingAs($this->userA2)->deleteJson("/comments/{$comment->id}");
        $response->assertForbidden();
    }

    public function test_moderator_can_delete_other_users_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
        ]);

        $response = $this->actingAs($this->managerA)->deleteJson("/comments/{$comment->id}");
        $response->assertOk();
        $this->assertSoftDeleted('document_comments', ['id' => $comment->id]);
    }

    public function test_replies_are_preserved_when_parent_comment_is_deleted(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Parent original',
        ]);

        $reply = DocumentComment::factory()->reply($parent)->create([
            'user_id' => $this->userA2->id,
            'content' => 'Réponse active',
        ]);

        $parent->delete();

        $this->assertSoftDeleted('document_comments', ['id' => $parent->id]);
        $this->assertDatabaseHas('document_comments', [
            'id' => $reply->id,
            'deleted_at' => null,
            'content' => 'Réponse active',
        ]);
    }

    public function test_deleted_parent_comment_displays_masked_content_in_list(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Parent sensible',
        ]);

        DocumentComment::factory()->reply($parent)->create([
            'user_id' => $this->userA2->id,
            'content' => 'Réponse conservée',
        ]);

        $parent->delete();

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('[Commentaire supprimé]', $data[0]['content']);
        $this->assertCount(1, $data[0]['replies']);
        $this->assertSame('Réponse conservée', $data[0]['replies'][0]['content']);
    }

    // ==========================================
    // 5. RESTAURATION
    // ==========================================

    public function test_author_can_restore_deleted_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
            'content' => 'Texte avant suppression',
        ]);
        $comment->delete();

        $response = $this->actingAs($this->userA1)->postJson("/comments/{$comment->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('document_comments', [
            'id' => $comment->id,
            'deleted_at' => null,
            'content' => 'Texte avant suppression',
        ]);
    }

    public function test_moderator_can_restore_deleted_comment(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
        ]);
        $comment->delete();

        $response = $this->actingAs($this->managerA)->postJson("/comments/{$comment->id}/restore");
        $response->assertOk();
        $this->assertNull($comment->fresh()->deleted_at);
    }

    public function test_cannot_restore_comment_of_trashed_document(): void
    {
        $doc = $this->createDocument();
        $comment = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
        ]);
        $comment->delete();
        $doc->delete();

        $response = $this->actingAs($this->userA1)->postJson("/comments/{$comment->id}/restore");
        $response->assertStatus(422);
    }

    // ==========================================
    // 6. RÉPONSES & PROFONDEUR DE DISCUSSION
    // ==========================================

    public function test_user_can_reply_to_root_comment(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create([
            'user_id' => $this->userA1->id,
        ]);

        $response = $this->actingAs($this->userA2)->postJson("/comments/{$parent->id}/reply", [
            'content' => 'Ma réponse au commentaire racine.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_comments', [
            'document_id' => $doc->id,
            'parent_id' => $parent->id,
            'user_id' => $this->userA2->id,
            'content' => 'Ma réponse au commentaire racine.',
        ]);
    }

    public function test_reply_to_a_reply_is_rejected_max_depth_one(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create();
        $reply = DocumentComment::factory()->reply($parent)->create();

        $response = $this->actingAs($this->userA1)->postJson("/comments/{$reply->id}/reply", [
            'content' => 'Tentative de réponse imbriquée niveau 2',
        ]);

        $response->assertStatus(422);
    }

    public function test_reply_to_deleted_comment_is_rejected(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create();
        $parent->delete();

        $response = $this->actingAs($this->userA2)->postJson("/comments/{$parent->id}/reply", [
            'content' => 'Réponse sur parent supprimé',
        ]);

        $response->assertNotFound();
    }

    public function test_reply_to_comment_from_another_tenant_is_forbidden(): void
    {
        $docB = $this->createDocument(['organization_id' => $this->orgB->id, 'uploaded_by' => $this->userB1->id]);
        $parentB = DocumentComment::factory()->forDocument($docB)->create(['user_id' => $this->userB1->id]);

        $response = $this->actingAs($this->userA1)->postJson("/comments/{$parentB->id}/reply", [
            'content' => 'Réponse cross-tenant',
        ]);

        $response->assertForbidden();
    }

    public function test_reply_on_archived_document_is_rejected(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create();
        $doc->update(['status' => 'archived']);

        $response = $this->actingAs($this->userA1)->postJson("/comments/{$parent->id}/reply", [
            'content' => 'Réponse sur document archivé',
        ]);

        $response->assertStatus(422);
    }

    // ==========================================
    // 7. CYCLE DE VIE DOCUMENTAIRE (ARCHIVE / TRASH)
    // ==========================================

    public function test_comments_can_be_read_on_active_document(): void
    {
        $doc = $this->createDocument();
        DocumentComment::factory()->forDocument($doc)->create(['content' => 'Commentaire lisible']);

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");

        $response->assertOk();
        $response->assertJsonFragment(['content' => 'Commentaire lisible']);
    }

    public function test_comments_can_be_read_on_archived_document(): void
    {
        $doc = $this->createDocument();
        DocumentComment::factory()->forDocument($doc)->create(['content' => 'Commentaire archivé']);
        $doc->update(['status' => 'archived']);

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");

        $response->assertOk();
        $response->assertJsonFragment(['content' => 'Commentaire archivé']);
    }

    public function test_creating_comment_on_archived_document_is_rejected(): void
    {
        $doc = $this->createDocument();
        $doc->update(['status' => 'archived']);

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Nouveau commentaire refusé',
        ]);

        $response->assertStatus(422);
    }

    public function test_comments_cannot_be_read_on_trashed_document(): void
    {
        $doc = $this->createDocument();
        DocumentComment::factory()->forDocument($doc)->create();
        $doc->delete();

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");

        $response->assertNotFound();
    }

    public function test_creating_comment_on_trashed_document_is_rejected(): void
    {
        $doc = $this->createDocument();
        $doc->delete();

        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Commentaire corbeille',
        ]);

        $response->assertNotFound();
    }

    public function test_comments_become_readable_again_after_document_restored_from_trash(): void
    {
        $doc = $this->createDocument();
        DocumentComment::factory()->forDocument($doc)->create(['content' => 'Commentaire conservé']);
        $doc->delete();

        $doc->restore();

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");
        $response->assertOk();
        $response->assertJsonFragment(['content' => 'Commentaire conservé']);
    }

    // ==========================================
    // 8. PAGINATION & ORDRE
    // ==========================================

    public function test_root_comments_are_paginated(): void
    {
        $doc = $this->createDocument();

        for ($i = 1; $i <= 25; $i++) {
            DocumentComment::factory()->forDocument($doc)->create([
                'content' => "Commentaire {$i}",
                'created_at' => now()->subMinutes(30 - $i),
            ]);
        }

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments?per_page=10");

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertSame(25, $response->json('total'));
    }

    public function test_root_comments_are_sorted_newest_first(): void
    {
        $doc = $this->createDocument();
        DocumentComment::factory()->forDocument($doc)->create(['content' => 'Ancien', 'created_at' => now()->subHour()]);
        DocumentComment::factory()->forDocument($doc)->create(['content' => 'Récent', 'created_at' => now()]);

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertSame('Récent', $data[0]['content']);
        $this->assertSame('Ancien', $data[1]['content']);
    }

    public function test_replies_are_sorted_oldest_first_chronological(): void
    {
        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create();

        DocumentComment::factory()->reply($parent)->create(['content' => 'Première réponse', 'created_at' => now()->subMinutes(10)]);
        DocumentComment::factory()->reply($parent)->create(['content' => 'Deuxième réponse', 'created_at' => now()]);

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments");

        $response->assertOk();
        $replies = $response->json('data.0.replies');
        $this->assertSame('Première réponse', $replies[0]['content']);
        $this->assertSame('Deuxième réponse', $replies[1]['content']);
    }

    public function test_filtering_comments_by_version_returns_only_matching_comments(): void
    {
        $doc = $this->createDocument();
        $v1 = $doc->versions()->first();
        $v2 = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 2,
            'file_name' => 'contrat_v2.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 2048,
            'storage_disk' => 'private',
            'storage_path' => 'documents/contrat_v2.pdf',
            'uploaded_by' => $this->adminA->id,
        ]);

        DocumentComment::factory()->forDocument($doc)->forVersion($v1)->create(['content' => 'Sur V1']);
        DocumentComment::factory()->forDocument($doc)->forVersion($v2)->create(['content' => 'Sur V2']);

        $response = $this->actingAs($this->userA1)->getJson("/documents/{$doc->id}/comments?version_id={$v2->id}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Sur V2', $data[0]['content']);
    }

    // ==========================================
    // 9. NOTIFICATIONS & AUDIT
    // ==========================================

    public function test_creating_comment_notifies_interested_users_excluding_author(): void
    {
        Notification::fake();

        $doc = $this->createDocument(['uploaded_by' => $this->adminA->id]);

        $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Nouveau commentaire collaboratif',
        ]);

        // adminA is document uploader -> must be notified
        Notification::assertSentTo($this->adminA, DocumentCommentedNotification::class);

        // userA1 is author -> must NOT be notified
        Notification::assertNotSentTo($this->userA1, DocumentCommentedNotification::class);

        // userB1 from another org -> must NOT be notified
        Notification::assertNotSentTo($this->userB1, DocumentCommentedNotification::class);
    }

    public function test_replying_to_comment_notifies_parent_author(): void
    {
        Notification::fake();

        $doc = $this->createDocument();
        $parent = DocumentComment::factory()->forDocument($doc)->create(['user_id' => $this->userA1->id]);

        $this->actingAs($this->userA2)->postJson("/comments/{$parent->id}/reply", [
            'content' => 'Réponse à Jean',
        ]);

        Notification::assertSentTo($this->userA1, DocumentCommentedNotification::class);
        Notification::assertNotSentTo($this->userA2, DocumentCommentedNotification::class);
    }

    public function test_audit_logs_are_generated_for_comment_lifecycle(): void
    {
        $doc = $this->createDocument();

        // 1. Create
        $comment = $this->commentService->create($this->userA1, $doc, 'Audit test');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.comment_created']);

        // 2. Reply
        $reply = $this->commentService->reply($this->userA2, $comment, 'Audit reply');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.comment_replied']);

        // 3. Update
        $this->commentService->update($this->userA1, $comment, 'Audit updated');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.comment_updated']);

        // 4. Delete
        $this->commentService->delete($this->userA1, $comment);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.comment_deleted']);

        // 5. Restore
        $this->commentService->restore($this->userA1, $comment);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.comment_restored']);
    }

    // ==========================================
    // 10. WORKFLOW COMPATIBILITY
    // ==========================================

    public function test_adding_comment_does_not_alter_workflow_state(): void
    {
        $doc = $this->createDocument();
        $wf = Workflow::factory()->create([
            'organization_id' => $this->orgA->id,
            'created_by' => $this->adminA->id,
            'is_active' => true,
        ]);

        WorkflowStep::create([
            'organization_id' => $this->orgA->id,
            'workflow_id' => $wf->id,
            'name' => 'Étape 1',
            'position' => 1,
            'approver_type' => WorkflowApproverType::User,
            'approver_user_id' => $this->userA1->id,
            'is_required' => true,
        ]);

        $instance = $this->workflowService->start($this->adminA, $doc, $wf);
        $this->assertSame(WorkflowStatus::InProgress, $instance->fresh()->status);

        // Add a comment on the document while in workflow
        $response = $this->actingAs($this->userA1)->postJson("/documents/{$doc->id}/comments", [
            'content' => 'Remarque relative à l\'approbation en cours',
        ]);

        $response->assertCreated();

        // Workflow state remains intact
        $this->assertSame(WorkflowStatus::InProgress, $instance->fresh()->status);
        $this->assertSame(1, $instance->fresh()->currentStep->position);
    }
}
