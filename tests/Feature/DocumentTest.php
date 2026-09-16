<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_document_successfully(): void
    {
        Storage::fake('private');

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $folder = Folder::factory()->create(['organization_id' => $org->id]);

        $service = new DocumentService;

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $data = [
            'organization_id' => $org->id,
            'uploaded_by' => $user->id,
            'folder_id' => $folder->id,
            'name' => 'Test Document',
        ];

        $document = $service->upload($data, $file);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'organization_id' => $org->id,
            'folder_id' => $folder->id,
            'uploaded_by' => $user->id,
            'name' => 'Test Document',
            'status' => 'active',
        ]);

        Storage::disk('private')->assertExists($document->storage_path);

        // Verify Version 1 was created alongside the document
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $document->id,
            'version_number' => 1,
            'uploaded_by' => $user->id,
        ]);
        $this->assertCount(1, $document->versions);
    }

    public function test_it_prevents_upload_to_folder_of_another_organization(): void
    {
        Storage::fake('private');

        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $folderB = Folder::factory()->create(['organization_id' => $orgB->id]);

        $service = new DocumentService;
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $data = [
            'organization_id' => $orgA->id,
            'uploaded_by' => $userA->id,
            'folder_id' => $folderB->id,
            'name' => 'Invalid Document',
        ];

        $this->expectExceptionMessage('Folder does not belong to your organization');
        $service->upload($data, $file);
    }

    public function test_it_soft_deletes_and_restores_a_document(): void
    {
        Storage::fake('private');

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $folder = Folder::factory()->create(['organization_id' => $org->id]);

        $service = new DocumentService;
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $data = [
            'organization_id' => $org->id,
            'uploaded_by' => $user->id,
            'folder_id' => $folder->id,
            'name' => 'Temp Document',
        ];
        $document = $service->upload($data, $file);

        $service->delete($document);
        $this->assertSoftDeleted('documents', ['id' => $document->id]);

        $service->restore($document);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'deleted_at' => null]);
    }
}
