<?php

namespace Tests\Feature;

use App\Enums\OcrStatus;
use App\Jobs\ProcessDocumentOcr;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\DocumentOcrCompletedNotification;
use App\Notifications\DocumentOcrFailedNotification;
use App\Services\AccessControlService;
use App\Services\DocumentService;
use App\Services\Ocr\Engines\TesseractEngine;
use App\Services\Ocr\Engines\TestingEngine;
use App\Services\OcrService;
use App\Services\SearchService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentOcrTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $adminA;

    protected User $userA;

    protected User $userB;

    protected DocumentService $documentService;

    protected OcrService $ocrService;

    protected SearchService $searchService;

    protected TestingEngine $testingEngine;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('r2');

        $this->seed(RolePermissionSeeder::class);

        $this->documentService = app(DocumentService::class);
        $this->ocrService = app(OcrService::class);
        $this->searchService = app(SearchService::class);

        // Bind testing engine to guarantee deterministic execution without local Tesseract binary
        $this->testingEngine = new TestingEngine;
        $this->ocrService->setEngine($this->testingEngine);
        $this->app->instance(OcrService::class, $this->ocrService);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->adminA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $permissions = [
            'documents.view', 'documents.create', 'documents.update',
            'documents.delete', 'documents.download', 'documents.archive',
            'documents.restore',
        ];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::create(['name' => 'admin_a_ocr', 'guard_name' => 'web']);
        $roleA->givePermissionTo($permissions);
        $this->adminA->assignRole($roleA);

        $readerRole = Role::create(['name' => 'reader_a_ocr', 'guard_name' => 'web']);
        $readerRole->givePermissionTo(['documents.view']);
        $this->userA->assignRole($readerRole);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgB->id);
        $roleB = Role::create(['name' => 'admin_b_ocr', 'guard_name' => 'web']);
        $roleB->givePermissionTo($permissions);
        $this->userB->assignRole($roleB);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    protected function actAsUser(User $user): static
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);

        return $this->actingAs($user);
    }

    /**
     * Create a mock document with physical file on storage.
     */
    protected function createDocumentWithFile(string $extension = 'pdf', string $disk = 'private', ?Organization $org = null): Document
    {
        $organization = $org ?? $this->orgA;
        $uploader = $organization->id === $this->orgA->id ? $this->adminA : $this->userB;
        $path = "organizations/{$organization->id}/documents/doc_".uniqid().".{$extension}";

        Storage::disk($disk)->put($path, 'Contenu brut pour le fichier test');

        $document = Document::factory()->create([
            'organization_id' => $organization->id,
            'uploaded_by' => $uploader->id,
            'extension' => $extension,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : "image/{$extension}",
            'storage_disk' => $disk,
            'storage_path' => $path,
            'size' => 1024,
        ]);

        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $uploader->id,
            'version_number' => 1,
            'extension' => $extension,
            'mime_type' => $document->mime_type,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'size' => 1024,
        ]);

        app(AccessControlService::class)->grantDocumentPermission($document, $uploader, 'view');
        app(AccessControlService::class)->grantDocumentPermission($document, $uploader, 'update');

        return $document->fresh();
    }

    // ==========================================
    // 1. Upload & Job Dispatch Tests
    // ==========================================

    public function test_ocr_is_dispatched_when_document_is_uploaded(): void
    {
        Queue::fake();

        $this->actAsUser($this->adminA);

        $file = UploadedFile::fake()->create('facture_scannée.pdf', 100, 'application/pdf');
        $this->documentService->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->adminA->id,
            'name' => 'Facture Test',
        ], $file);

        Queue::assertPushed(ProcessDocumentOcr::class, function ($job) {
            return $job->document->name === 'Facture Test';
        });
    }

    public function test_ocr_is_dispatched_when_new_version_is_uploaded(): void
    {
        Queue::fake();

        $this->actAsUser($this->adminA);
        $document = $this->createDocumentWithFile('pdf');

        $file = UploadedFile::fake()->create('facture_v2.pdf', 120, 'application/pdf');
        $this->documentService->uploadNewVersion($document, $file, 'Version révisée');

        Queue::assertPushed(ProcessDocumentOcr::class, function ($job) use ($document) {
            return $job->document->id === $document->id && $job->version !== null && $job->version->version_number === 2;
        });
    }

    // ==========================================
    // 2. OCR Processing Logic Tests
    // ==========================================

    public function test_ocr_service_processes_compatible_image_successfully(): void
    {
        $document = $this->createDocumentWithFile('png');
        $this->testingEngine->setFakeText('Contrat de prestation de services confidentiel numéro 9988');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertEquals('Contrat de prestation de services confidentiel numéro 9988', $ocr->extracted_text);
        $this->assertGreaterThan(0, $ocr->word_count);
        $this->assertNotNull($ocr->processed_at);
        $this->assertNull($ocr->error_message);

        $this->assertDatabaseHas('document_ocrs', [
            'document_id' => $document->id,
            'status' => 'completed',
            'word_count' => $ocr->word_count,
        ]);
    }

    public function test_ocr_service_skips_incompatible_file_format(): void
    {
        $document = $this->createDocumentWithFile('docx');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Skipped, $ocr->status);
        $this->assertNull($ocr->extracted_text);
        $this->assertStringContainsString('non pris en charge', $ocr->error_message);
    }

    public function test_ocr_service_handles_missing_file_gracefully(): void
    {
        $document = $this->createDocumentWithFile('pdf');
        // Delete the physical file from storage to trigger missing file error
        Storage::disk($document->storage_disk)->delete($document->storage_path);

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Failed, $ocr->status);
        $this->assertStringContainsString('introuvable', $ocr->error_message);
    }

    public function test_ocr_service_handles_engine_failure(): void
    {
        $document = $this->createDocumentWithFile('jpg');
        $this->testingEngine->shouldFail(true, 'Dépassement mémoire Tesseract');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Failed, $ocr->status);
        $this->assertEquals('Dépassement mémoire Tesseract', $ocr->error_message);
    }

    public function test_process_document_ocr_job_executes_asynchronously(): void
    {
        $document = $this->createDocumentWithFile('png');
        $this->testingEngine->setFakeText('Texte analysé en arrière plan par le job');

        $job = new ProcessDocumentOcr($document, $document->currentVersion);
        $job->handle($this->ocrService);

        $ocr = DocumentOcr::where('document_id', $document->id)->first();
        $this->assertNotNull($ocr);
        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertEquals('Texte analysé en arrière plan par le job', $ocr->extracted_text);
    }

    // ==========================================
    // 3. Versioning Isolation Tests
    // ==========================================

    public function test_version_isolation_different_versions_have_independent_ocr_records(): void
    {
        $this->actAsUser($this->adminA);
        $document = $this->createDocumentWithFile('pdf');
        $version1 = $document->currentVersion;

        // Process Version 1
        $this->testingEngine->setFakeText('Contenu textuel de la version 1');
        $ocrV1 = $this->ocrService->process($document, $version1);

        // Upload Version 2
        $file2 = UploadedFile::fake()->create('v2.pdf', 150, 'application/pdf');
        $version2 = $this->documentService->uploadNewVersion($document, $file2);

        // Process Version 2 with different text
        $this->testingEngine->setFakeText('Contenu textuel révisé de la version 2');
        $ocrV2 = $this->ocrService->process($document, $version2);

        $this->assertNotEquals($ocrV1->id, $ocrV2->id);
        $this->assertEquals('Contenu textuel de la version 1', $ocrV1->fresh()->extracted_text);
        $this->assertEquals('Contenu textuel révisé de la version 2', $ocrV2->fresh()->extracted_text);
        $this->assertEquals($version1->id, $ocrV1->document_version_id);
        $this->assertEquals($version2->id, $ocrV2->document_version_id);
    }

    // ==========================================
    // 4. Full-Text Search Integration Tests
    // ==========================================

    public function test_full_text_search_finds_document_by_extracted_ocr_text(): void
    {
        $this->actAsUser($this->adminA);
        $document = $this->createDocumentWithFile('pdf');

        // Text that does NOT appear in document name or description
        $secretKeyword = 'clausedeconfidentialitemaximale77';

        $this->testingEngine->setFakeText("Le présent accord contient une {$secretKeyword} régissant l'accord.");
        $this->ocrService->process($document);

        // Search for the secret keyword
        $results = $this->searchService->search($this->adminA, [
            'q' => $secretKeyword,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($document->id, $results->first()->id);
    }

    public function test_full_text_search_with_ocr_text_respects_tenant_isolation(): void
    {
        $this->actAsUser($this->adminA);
        // Create document in Organization A
        $docA = $this->createDocumentWithFile('pdf', 'private', $this->orgA);
        $targetTerm = 'motcleuniqueorganisationsolo123';
        $this->testingEngine->setFakeText("Texte OCR de l'organisation A avec {$targetTerm}");
        $this->ocrService->process($docA);

        // User B in Organization B searches for the same keyword
        $this->actAsUser($this->userB);
        $resultsB = $this->searchService->search($this->userB, [
            'q' => $targetTerm,
        ]);

        // User B must NOT see Organization A's document
        $this->assertCount(0, $resultsB);
    }

    public function test_full_text_search_with_ocr_text_respects_document_permissions(): void
    {
        // User without documents.view permission
        $unauthorizedUser = User::factory()->create(['organization_id' => $this->orgA->id]);

        $document = $this->createDocumentWithFile('pdf', 'private', $this->orgA);
        $this->testingEngine->setFakeText('Informations financières top secrètes 98765');
        $this->ocrService->process($document);

        $this->actAsUser($unauthorizedUser);
        $results = $this->searchService->search($unauthorizedUser, [
            'q' => 'financières',
        ]);

        $this->assertCount(0, $results);
    }

    // ==========================================
    // 5. API Sanctum Endpoint Tests
    // ==========================================

    public function test_api_get_document_ocr_status_and_text(): void
    {
        $document = $this->createDocumentWithFile('png');
        $this->testingEngine->setFakeText('Données textuelles API OCR');
        $this->ocrService->process($document);

        $response = $this->actAsUser($this->adminA)
            ->getJson("/api/v1/documents/{$document->id}/ocr");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.has_text', true)
            ->assertJsonPath('data.extracted_text', 'Données textuelles API OCR');
    }

    public function test_api_get_document_ocr_isolated_between_tenants(): void
    {
        $docA = $this->createDocumentWithFile('pdf', 'private', $this->orgA);
        $this->testingEngine->setFakeText('Texte confidentiel Org A');
        $this->ocrService->process($docA);

        // User B in Org B attempts to access Org A's OCR
        $response = $this->actAsUser($this->userB)
            ->getJson("/api/v1/documents/{$docA->id}/ocr");

        $response->assertStatus(403);
    }

    public function test_api_retry_document_ocr_dispatches_job(): void
    {
        Queue::fake();

        $document = $this->createDocumentWithFile('png');

        $response = $this->actAsUser($this->adminA)
            ->postJson("/api/v1/documents/{$document->id}/ocr/retry");

        $response->assertAccepted()
            ->assertJsonPath('data.status', 'pending');

        Queue::assertPushed(ProcessDocumentOcr::class, function ($job) use ($document) {
            return $job->document->id === $document->id;
        });
    }

    public function test_api_retry_document_ocr_denied_without_update_permission(): void
    {
        $document = $this->createDocumentWithFile('png');

        // userA has only documents.view, not documents.update
        $response = $this->actAsUser($this->userA)
            ->postJson("/api/v1/documents/{$document->id}/ocr/retry");

        $response->assertForbidden();
    }

    // ==========================================
    // 6. Web Routes & Controller Tests
    // ==========================================

    public function test_web_retry_ocr_route_dispatches_job(): void
    {
        Queue::fake();

        $document = $this->createDocumentWithFile('png');

        $response = $this->actAsUser($this->adminA)
            ->post("/documents/{$document->id}/ocr/retry");

        $response->assertRedirect();
        Queue::assertPushed(ProcessDocumentOcr::class);
    }

    public function test_web_retry_ocr_denied_without_permission(): void
    {
        $document = $this->createDocumentWithFile('png');

        // userA has view only
        $response = $this->actAsUser($this->userA)
            ->post("/documents/{$document->id}/ocr/retry");

        $response->assertForbidden();
    }

    // ==========================================
    // 7. Audit & Notification Tests
    // ==========================================

    public function test_audit_log_recorded_on_ocr_start_complete_and_failure(): void
    {
        $document = $this->createDocumentWithFile('jpg');
        $this->testingEngine->setFakeText('Texte pour audit test');

        $this->ocrService->process($document);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.ocr_started',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'result' => 'success',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.ocr_completed',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'result' => 'success',
        ]);
    }

    public function test_notification_sent_on_ocr_complete_and_failure(): void
    {
        Notification::fake();

        $document = $this->createDocumentWithFile('jpg');
        $this->testingEngine->setFakeText('Texte pour notification');

        $this->ocrService->process($document);

        Notification::assertSentTo(
            $this->adminA,
            DocumentOcrCompletedNotification::class
        );

        // Test failure notification
        Notification::fake();
        $this->testingEngine->shouldFail(true, 'Erreur fatale');

        $this->ocrService->process($document);

        Notification::assertSentTo(
            $this->adminA,
            DocumentOcrFailedNotification::class
        );
    }

    public function test_storage_r2_file_processed_by_ocr(): void
    {
        $document = $this->createDocumentWithFile('png', 'r2');
        $this->testingEngine->setFakeText('Document stocké sur Cloudflare R2 privé');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertEquals('Document stocké sur Cloudflare R2 privé', $ocr->extracted_text);
        $this->assertEquals('r2', $document->storage_disk);
    }

    public function test_tesseract_engine_availability_check(): void
    {
        $engine = new TesseractEngine('non_existent_binary_xyz_123');

        $this->assertFalse($engine->isAvailable());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("n'est pas disponible");

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test');

        try {
            $engine->extractText($tempFile, 'png');
        } finally {
            @unlink($tempFile);
        }
    }
}
