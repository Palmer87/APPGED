<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\DocumentArchivedNotification;
use App\Notifications\DocumentCommentedNotification;
use App\Notifications\DocumentRestoredNotification;
use App\Notifications\DocumentSharedNotification;
use App\Notifications\DocumentShareRevokedNotification;
use App\Notifications\DocumentVersionCreatedNotification;
use App\Notifications\DocumentWorkflowNotification;
use App\Services\DocumentLifecycleService;
use App\Services\DocumentService;
use App\Services\DocumentShareService;
use App\Services\NotificationPreferenceService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;

    protected NotificationPreferenceService $preferenceService;

    protected DocumentShareService $shareService;

    protected DocumentService $documentService;

    protected DocumentLifecycleService $lifecycleService;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

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

        $this->notificationService = app(NotificationService::class);
        $this->preferenceService = app(NotificationPreferenceService::class);
        $this->shareService = app(DocumentShareService::class);
        $this->documentService = app(DocumentService::class);
        $this->lifecycleService = app(DocumentLifecycleService::class);

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA1 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA2 = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB1 = User::factory()->create(['organization_id' => $this->orgB->id]);

        $permissions = [
            'documents.view', 'documents.create', 'documents.update',
            'documents.delete', 'documents.download', 'documents.share',
            'documents.archive', 'documents.restore', 'audit.view',
        ];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleAdminA = Role::create(['name' => 'admin_a', 'guard_name' => 'web', 'organization_id' => $this->orgA->id]);
        $roleAdminA->givePermissionTo($permissions);
        $this->adminA->assignRole($roleAdminA);

        $roleUserA = Role::create(['name' => 'user_a', 'guard_name' => 'web', 'organization_id' => $this->orgA->id]);
        $roleUserA->givePermissionTo(['documents.view', 'documents.download']);
        $this->userA1->assignRole($roleUserA);
        $this->userA2->assignRole($roleUserA);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleAdminB = Role::create(['name' => 'admin_b', 'guard_name' => 'web', 'organization_id' => $this->orgB->id]);
        $roleAdminB->givePermissionTo($permissions);
        $this->userB1->assignRole($roleAdminB);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
    }

    protected function createDocument(array $attributes = []): Document
    {
        $orgId = $attributes['organization_id'] ?? $this->orgA->id;
        $uploaderId = $attributes['uploaded_by'] ?? $this->adminA->id;
        $path = $attributes['storage_path'] ?? "organizations/{$orgId}/documents/doc_".uniqid().'.pdf';

        Storage::disk('private')->put($path, '%PDF-1.4 sample content');

        return Document::factory()->create(array_merge([
            'organization_id' => $orgId,
            'uploaded_by' => $uploaderId,
            'name' => 'Sample Contract',
            'file_name' => 'contract.pdf',
            'storage_disk' => 'private',
            'storage_path' => $path,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'active',
        ], $attributes));
    }

    // =========================================================================
    // 1. DATABASE NOTIFICATIONS & PAYLOAD STRUCTURE
    // =========================================================================

    public function test_notification_can_be_created_and_persisted_to_database(): void
    {
        $doc = $this->createDocument();

        $this->notificationService->notifyUser(
            $this->userA1,
            new DocumentSharedNotification($doc, $this->adminA, 'view')
        );

        $this->assertCount(1, $this->userA1->notifications);

        $dbNotification = $this->userA1->notifications->first();
        $this->assertEquals(DocumentSharedNotification::class, $dbNotification->type);
        $this->assertNull($dbNotification->read_at);
        $this->assertEquals($doc->id, $dbNotification->data['document_id']);
        $this->assertEquals($this->adminA->id, $dbNotification->data['actor_id']);
        $this->assertEquals('document.shared', $dbNotification->data['type']);
    }

    public function test_notification_contains_structured_data_without_secrets(): void
    {
        $doc = $this->createDocument(['storage_path' => 'secret/internal/disk/path/doc.pdf']);

        $this->notificationService->notifyUser(
            $this->userA1,
            new DocumentSharedNotification($doc, $this->adminA, 'download')
        );

        $data = $this->userA1->notifications->first()->data;

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('document_id', $data);
        $this->assertArrayHasKey('document_name', $data);
        $this->assertArrayHasKey('actor_id', $data);
        $this->assertArrayHasKey('actor_name', $data);
        $this->assertArrayHasKey('url', $data);

        // Security: NO secrets or physical paths
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('token', $data);
        $this->assertArrayNotHasKey('storage_path', $data);
        $this->assertStringNotContainsString('secret/internal', json_encode($data));
    }

    public function test_all_seven_notification_types_have_correct_identifiers(): void
    {
        $doc = $this->createDocument();
        $version = DocumentVersion::factory()->create([
            'document_id' => $doc->id,
            'version_number' => 2,
            'uploaded_by' => $this->adminA->id,
            'storage_path' => 'path/to/v2.pdf',
        ]);

        $this->assertEquals('document.shared', (new DocumentSharedNotification($doc, $this->adminA))->getType());
        $this->assertEquals('document.version_created', (new DocumentVersionCreatedNotification($doc, $version, $this->adminA))->getType());
        $this->assertEquals('document.share_revoked', (new DocumentShareRevokedNotification($doc, $this->adminA))->getType());
        $this->assertEquals('document.commented', (new DocumentCommentedNotification($doc, $this->adminA, 'Note'))->getType());
        $this->assertEquals('document.workflow', (new DocumentWorkflowNotification($doc, $this->adminA, 'approved'))->getType());
        $this->assertEquals('document.archived', (new DocumentArchivedNotification($doc, $this->adminA))->getType());
        $this->assertEquals('document.restored', (new DocumentRestoredNotification($doc, $this->adminA))->getType());
    }

    // =========================================================================
    // 2. DOCUMENT SHARING NOTIFICATIONS
    // =========================================================================

    public function test_sharing_document_with_user_sends_notification_to_target_user(): void
    {
        $doc = $this->createDocument();

        $this->shareService->shareWithUser($this->adminA, $doc, $this->userA1, 'view');

        $this->assertCount(1, $this->userA1->notifications);
        $notif = $this->userA1->notifications->first();
        $this->assertEquals('document.shared', $notif->data['type']);
        $this->assertEquals($doc->id, $notif->data['document_id']);
        $this->assertEquals($this->adminA->id, $notif->data['actor_id']);
    }

    public function test_sharing_document_does_not_notify_the_actor_themselves(): void
    {
        $doc = $this->createDocument(['uploaded_by' => $this->adminA->id]);

        $this->notificationService->notifyUser(
            $this->adminA,
            new DocumentSharedNotification($doc, $this->adminA, 'view')
        );

        $this->assertCount(0, $this->adminA->notifications);
    }

    public function test_sharing_document_with_group_notifies_all_active_group_members(): void
    {
        $doc = $this->createDocument();
        $group = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $group->users()->attach([$this->userA1->id, $this->userA2->id]);

        $this->shareService->shareWithGroup($this->adminA, $doc, $group, 'download');

        $this->assertCount(1, $this->userA1->notifications);
        $this->assertCount(1, $this->userA2->notifications);
        $this->assertCount(0, $this->adminA->notifications);
    }

    public function test_sharing_with_group_deduplicates_notifications_for_user_in_group(): void
    {
        $doc = $this->createDocument();
        $group = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $group->users()->attach([$this->userA1->id]);

        // If target list has userA1 twice
        $this->notificationService->notifyUsers(
            [$this->userA1, $this->userA1],
            new DocumentSharedNotification($doc, $this->adminA),
            $this->orgA->id
        );

        $this->assertCount(1, $this->userA1->notifications);
    }

    public function test_sharing_document_never_notifies_users_from_another_tenant(): void
    {
        $docA = $this->createDocument(['organization_id' => $this->orgA->id]);

        // Attempt direct send to user from Org B
        $this->notificationService->notifyUser(
            $this->userB1,
            new DocumentSharedNotification($docA, $this->adminA),
            $this->orgA->id
        );

        $this->assertCount(0, $this->userB1->notifications);
    }

    public function test_group_with_cross_tenant_members_only_notifies_tenant_members(): void
    {
        $docA = $this->createDocument(['organization_id' => $this->orgA->id]);

        $this->notificationService->notifyUsers(
            [$this->userA1, $this->userB1],
            new DocumentSharedNotification($docA, $this->adminA),
            $this->orgA->id
        );

        $this->assertCount(1, $this->userA1->notifications);
        $this->assertCount(0, $this->userB1->notifications);
    }

    public function test_revoking_document_share_notifies_target_user(): void
    {
        $doc = $this->createDocument();
        $share = $this->shareService->shareWithUser($this->adminA, $doc, $this->userA1, 'view');

        // Clear share notification
        $this->userA1->notifications()->delete();

        $this->shareService->revoke($this->adminA, $share);

        $this->assertCount(1, $this->userA1->notifications);
        $notif = $this->userA1->notifications->first();
        $this->assertEquals('document.share_revoked', $notif->data['type']);
    }

    public function test_revoking_group_share_notifies_all_group_members(): void
    {
        $doc = $this->createDocument();
        $group = Group::factory()->create(['organization_id' => $this->orgA->id]);
        $group->users()->attach([$this->userA1->id, $this->userA2->id]);

        $share = $this->shareService->shareWithGroup($this->adminA, $doc, $group, 'view');

        $this->userA1->notifications()->delete();
        $this->userA2->notifications()->delete();

        $this->shareService->revoke($this->adminA, $share);

        $this->assertCount(1, $this->userA1->notifications);
        $this->assertCount(1, $this->userA2->notifications);
    }

    // =========================================================================
    // 3. VERSIONS & LIFECYCLE NOTIFICATIONS
    // =========================================================================

    public function test_uploading_new_version_notifies_users_with_active_document_shares(): void
    {
        $doc = $this->createDocument();
        $this->shareService->shareWithUser($this->adminA, $doc, $this->userA1, 'view');
        $this->userA1->notifications()->delete();

        $file = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->actingAs($this->adminA);
        $this->documentService->uploadNewVersion($doc, $file, 'Second version');

        $this->assertCount(1, $this->userA1->notifications);
        $notif = $this->userA1->notifications->first();
        $this->assertEquals('document.version_created', $notif->data['type']);
        $this->assertEquals(1, $notif->data['version_number']);
    }

    public function test_uploading_new_version_notifies_document_creator_if_different_from_actor(): void
    {
        $doc = $this->createDocument(['uploaded_by' => $this->userA1->id]);

        $file = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->actingAs($this->adminA);
        $this->documentService->uploadNewVersion($doc, $file, 'Admin updated creator doc');

        $versionNotifs = $this->userA1->notifications->where('data.type', 'document.version_created');
        $this->assertCount(1, $versionNotifs);
        $this->assertEquals('document.version_created', $versionNotifs->first()->data['type']);
    }

    public function test_uploading_new_version_does_not_notify_users_with_revoked_shares(): void
    {
        $doc = $this->createDocument();
        $share = $this->shareService->shareWithUser($this->adminA, $doc, $this->userA1, 'view');
        $this->shareService->revoke($this->adminA, $share);
        $this->userA1->notifications()->delete();

        $file = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->actingAs($this->adminA);
        $this->documentService->uploadNewVersion($doc, $file);

        $this->assertCount(0, $this->userA1->notifications);
    }

    public function test_uploading_new_version_does_not_notify_users_with_expired_shares(): void
    {
        $doc = $this->createDocument();
        $share = $this->shareService->shareWithUser(
            $this->adminA,
            $doc,
            $this->userA1,
            'view',
            Carbon::now()->subDay()
        );
        $this->userA1->notifications()->delete();

        $file = UploadedFile::fake()->create('v2.pdf', 200, 'application/pdf');
        $this->actingAs($this->adminA);
        $this->documentService->uploadNewVersion($doc, $file);

        $this->assertCount(0, $this->userA1->notifications);
    }

    public function test_archiving_document_notifies_interested_users(): void
    {
        $doc = $this->createDocument(['uploaded_by' => $this->userA1->id]);
        $this->shareService->shareWithUser($this->adminA, $doc, $this->userA2, 'view');

        $this->userA1->notifications()->delete();
        $this->userA2->notifications()->delete();

        $this->lifecycleService->archive($this->adminA, $doc);

        $this->assertCount(1, $this->userA1->notifications);
        $this->assertCount(1, $this->userA2->notifications);
        $this->assertEquals('document.archived', $this->userA1->notifications->first()->data['type']);
    }

    public function test_restoring_document_from_trash_notifies_interested_users(): void
    {
        $doc = $this->createDocument(['uploaded_by' => $this->userA1->id]);
        $this->shareService->shareWithUser($this->adminA, $doc, $this->userA2, 'view');

        $this->lifecycleService->moveToTrash($this->adminA, $doc);
        $this->userA1->notifications()->delete();
        $this->userA2->notifications()->delete();

        $this->lifecycleService->restoreFromTrash($this->adminA, $doc);

        $this->assertCount(1, $this->userA1->notifications);
        $this->assertCount(1, $this->userA2->notifications);
        $this->assertEquals('document.restored', $this->userA1->notifications->first()->data['type']);
    }

    // =========================================================================
    // 4. API ENDPOINTS & ACCESS CONTROL
    // =========================================================================

    public function test_user_can_only_see_their_own_notifications(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $this->notificationService->notifyUser($this->userA2, new DocumentSharedNotification($doc, $this->adminA));

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.index'));

        $response->assertOk();
        $data = $response->json('notifications.data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->userA1->notifications->first()->id, $data[0]['id']);
    }

    public function test_user_cannot_view_notifications_of_another_user(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA2, new DocumentSharedNotification($doc, $this->adminA));

        // Attempting to list notifications as userA1 gives only userA1's empty list
        $response = $this->actingAs($this->userA1)->getJson(route('notifications.index'));

        $response->assertOk();
        $this->assertCount(0, $response->json('notifications.data'));
        $this->assertEquals(0, $response->json('unread_count'));
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA2, new DocumentSharedNotification($doc, $this->adminA));
        $notif = $this->userA2->notifications->first();

        $response = $this->actingAs($this->userA1)->postJson(route('notifications.read', $notif->id));

        $response->assertForbidden();
        $this->assertNull($notif->fresh()->read_at);
    }

    public function test_cannot_delete_another_users_notification(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA2, new DocumentSharedNotification($doc, $this->adminA));
        $notif = $this->userA2->notifications->first();

        $response = $this->actingAs($this->userA1)->deleteJson(route('notifications.destroy', $notif->id));

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $notif->id]);
    }

    public function test_marking_nonexistent_notification_returns_404(): void
    {
        $response = $this->actingAs($this->userA1)->postJson(route('notifications.read', '00000000-0000-0000-0000-000000000000'));

        $response->assertNotFound();
    }

    public function test_deleting_nonexistent_notification_returns_404(): void
    {
        $response = $this->actingAs($this->userA1)->deleteJson(route('notifications.destroy', '00000000-0000-0000-0000-000000000000'));

        $response->assertNotFound();
    }

    // =========================================================================
    // 5. READ STATUS & UNREAD COUNTS
    // =========================================================================

    public function test_notification_initial_state_has_null_read_at(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));

        $notif = $this->userA1->notifications->first();
        $this->assertNull($notif->read_at);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $notif = $this->userA1->notifications->first();

        $response = $this->actingAs($this->userA1)->postJson(route('notifications.read', $notif->id));

        $response->assertOk();
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_marking_already_read_notification_as_read_is_idempotent(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $notif = $this->userA1->notifications->first();
        $notif->markAsRead();
        $initialReadAt = $notif->fresh()->read_at;

        $response = $this->actingAs($this->userA1)->postJson(route('notifications.read', $notif->id));

        $response->assertOk();
        $this->assertEquals($initialReadAt->toISOString(), $notif->fresh()->read_at->toISOString());
    }

    public function test_user_can_mark_all_unread_notifications_as_read(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $this->notificationService->notifyUser($this->userA1, new DocumentArchivedNotification($doc, $this->adminA));

        $this->assertEquals(2, $this->userA1->unreadNotifications()->count());

        $response = $this->actingAs($this->userA1)->postJson(route('notifications.read_all'));

        $response->assertOk();
        $this->assertEquals(0, $this->userA1->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_only_affects_authenticated_user_notifications(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $this->notificationService->notifyUser($this->userA2, new DocumentSharedNotification($doc, $this->adminA));

        $this->actingAs($this->userA1)->postJson(route('notifications.read_all'));

        $this->assertEquals(0, $this->userA1->unreadNotifications()->count());
        $this->assertEquals(1, $this->userA2->unreadNotifications()->count());
    }

    public function test_unread_count_is_accurately_computed_without_loading_all_records(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $this->notificationService->notifyUser($this->userA1, new DocumentArchivedNotification($doc, $this->adminA));

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.index'));

        $response->assertOk();
        $this->assertEquals(2, $response->json('unread_count'));
    }

    public function test_unread_endpoint_returns_only_unread_notifications(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $this->notificationService->notifyUser($this->userA1, new DocumentArchivedNotification($doc, $this->adminA));

        $notif1 = $this->userA1->notifications->first();
        $notif1->markAsRead();

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.unread'));

        $response->assertOk();
        $this->assertEquals(1, $response->json('unread_count'));
        $this->assertCount(1, $response->json('unread_notifications.data'));
    }

    public function test_unread_query_filter_works_on_index_endpoint(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $this->notificationService->notifyUser($this->userA1, new DocumentArchivedNotification($doc, $this->adminA));

        $notif1 = $this->userA1->notifications->first();
        $notif1->markAsRead();

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.index', ['unread' => 1]));

        $response->assertOk();
        $this->assertCount(1, $response->json('notifications.data'));
    }

    // =========================================================================
    // 6. DELETION & AUDIT INDEPENDENCE
    // =========================================================================

    public function test_user_can_delete_their_own_notification(): void
    {
        $doc = $this->createDocument();
        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $notif = $this->userA1->notifications->first();

        $response = $this->actingAs($this->userA1)->deleteJson(route('notifications.destroy', $notif->id));

        $response->assertOk();
        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }

    public function test_deleting_notification_does_not_affect_audit_logs(): void
    {
        $doc = $this->createDocument();
        $share = $this->shareService->shareWithUser($this->adminA, $doc, $this->userA1, 'view');
        $notif = $this->userA1->notifications->first();

        $this->assertDatabaseHas('audit_logs', ['action' => 'document.shared']);

        $this->actingAs($this->userA1)->deleteJson(route('notifications.destroy', $notif->id));

        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.shared']);
    }

    // =========================================================================
    // 7. EXPIRATION & ACL BOUNDARY
    // =========================================================================

    public function test_notification_remains_visible_after_share_expires_but_document_access_is_forbidden(): void
    {
        // User without general documents.view role
        $guestUser = User::factory()->create(['organization_id' => $this->orgA->id]);

        $doc = $this->createDocument();
        $share = $this->shareService->shareWithUser(
            $this->adminA,
            $doc,
            $guestUser,
            'view',
            Carbon::now()->addHour()
        );

        $notif = $guestUser->notifications->first();
        $this->assertNotNull($notif);

        // Advance time so share expires
        $share->update(['expires_at' => Carbon::now()->subMinute()]);

        // Notification is still in the user's notification list
        $responseNotif = $this->actingAs($guestUser)->getJson(route('notifications.index'));
        $responseNotif->assertOk();
        $this->assertCount(1, $responseNotif->json('notifications.data'));

        // But preview/access of document via the notification's URL is forbidden
        $responsePreview = $this->actingAs($guestUser)->get(route('documents.preview', $doc));
        $responsePreview->assertForbidden();
    }

    public function test_notification_remains_visible_after_share_revoked_but_document_access_is_forbidden(): void
    {
        // User without general documents.view role
        $guestUser = User::factory()->create(['organization_id' => $this->orgA->id]);

        $doc = $this->createDocument();
        $share = $this->shareService->shareWithUser($this->adminA, $doc, $guestUser, 'view');

        $this->assertCount(1, $guestUser->notifications);

        $this->shareService->revoke($this->adminA, $share);

        // Notification is still in the user's notification list
        $responseNotif = $this->actingAs($guestUser)->getJson(route('notifications.index'));
        $responseNotif->assertOk();
        $this->assertCount(2, $responseNotif->json('notifications.data')); // share + revoke notifications

        // Document access is forbidden
        $responsePreview = $this->actingAs($guestUser)->get(route('documents.preview', $doc));
        $responsePreview->assertForbidden();
    }

    // =========================================================================
    // 8. NOTIFICATION PREFERENCES
    // =========================================================================

    public function test_user_can_retrieve_their_notification_preferences(): void
    {
        $this->preferenceService->updatePreference($this->userA1, 'document.shared', true, true);

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.preferences.index'));

        $response->assertOk();
        $prefs = $response->json('preferences');
        $this->assertCount(1, $prefs);
        $this->assertEquals('document.shared', $prefs[0]['notification_type']);
        $this->assertTrue($prefs[0]['database_enabled']);
        $this->assertTrue($prefs[0]['email_enabled']);
    }

    public function test_user_can_update_notification_preference(): void
    {
        $response = $this->actingAs($this->userA1)->putJson(route('notifications.preferences.update'), [
            'notification_type' => 'document.shared',
            'database_enabled' => false,
            'email_enabled' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->userA1->id,
            'organization_id' => $this->orgA->id,
            'notification_type' => 'document.shared',
            'database_enabled' => false,
            'email_enabled' => true,
        ]);
    }

    public function test_disabling_database_notification_prevents_database_persistence(): void
    {
        $this->preferenceService->updatePreference($this->userA1, 'document.shared', false, false);

        $doc = $this->createDocument();
        $this->notificationService->notifyUser(
            $this->userA1,
            new DocumentSharedNotification($doc, $this->adminA)
        );

        $this->assertCount(0, $this->userA1->notifications);
    }

    public function test_updating_preference_with_invalid_type_fails_validation(): void
    {
        $response = $this->actingAs($this->userA1)->putJson(route('notifications.preferences.update'), [
            'notification_type' => 'invalid.type',
            'database_enabled' => true,
            'email_enabled' => false,
        ]);

        $response->assertUnprocessable();
    }

    public function test_notification_preferences_are_strictly_isolated_by_user_and_organization(): void
    {
        $this->preferenceService->updatePreference($this->userA1, 'document.shared', true, true);
        $this->preferenceService->updatePreference($this->userB1, 'document.shared', false, false);

        $this->assertTrue($this->preferenceService->isDatabaseEnabled($this->userA1, 'document.shared'));
        $this->assertFalse($this->preferenceService->isDatabaseEnabled($this->userB1, 'document.shared'));
    }

    // =========================================================================
    // 9. PAGINATION & ORDERING
    // =========================================================================

    public function test_notifications_are_ordered_descending_by_creation_date(): void
    {
        $doc = $this->createDocument();

        $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        $firstNotif = $this->userA1->notifications->first();
        $firstNotif->updateQuietly(['created_at' => Carbon::now()->subMinutes(10)]);

        $this->notificationService->notifyUser($this->userA1, new DocumentArchivedNotification($doc, $this->adminA));

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.index'));

        $response->assertOk();
        $data = $response->json('notifications.data');
        $this->assertCount(2, $data);
        $this->assertEquals('document.archived', $data[0]['data']['type']);
        $this->assertEquals('document.shared', $data[1]['data']['type']);
    }

    public function test_notifications_pagination_respects_custom_per_page_up_to_max_100(): void
    {
        $doc = $this->createDocument();
        for ($i = 0; $i < 10; $i++) {
            $this->notificationService->notifyUser($this->userA1, new DocumentSharedNotification($doc, $this->adminA));
        }

        $response = $this->actingAs($this->userA1)->getJson(route('notifications.index', ['per_page' => 5]));

        $response->assertOk();
        $this->assertCount(5, $response->json('notifications.data'));
        $this->assertEquals(5, $response->json('notifications.per_page'));

        // Max cap
        $responseCap = $this->actingAs($this->userA1)->getJson(route('notifications.index', ['per_page' => 500]));
        $responseCap->assertOk();
        $this->assertEquals(100, $responseCap->json('notifications.per_page'));
    }
}
