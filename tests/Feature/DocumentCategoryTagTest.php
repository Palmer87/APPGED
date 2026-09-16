<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DocumentCategoryTagTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected DocumentService $service;

    protected Document $documentA;

    protected Document $documentB;

    protected Category $categoryA1;

    protected Category $categoryA2;

    protected Category $categoryB;

    protected Tag $tagA1;

    protected Tag $tagA2;

    protected Tag $tagB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->service = new DocumentService;

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->for($this->orgA)->create();
        $this->userB = User::factory()->for($this->orgB)->create();

        $fileA = UploadedFile::fake()->create('docA.pdf', 100, 'application/pdf');
        $this->documentA = $this->service->upload([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'storage_disk' => 'private',
        ], $fileA);

        $fileB = UploadedFile::fake()->create('docB.pdf', 100, 'application/pdf');
        $this->documentB = $this->service->upload([
            'organization_id' => $this->orgB->id,
            'uploaded_by' => $this->userB->id,
            'storage_disk' => 'private',
        ], $fileB);

        $this->categoryA1 = Category::factory()->forOrganization($this->orgA)->create(['name' => 'Cat A1']);
        $this->categoryA2 = Category::factory()->forOrganization($this->orgA)->create(['name' => 'Cat A2']);
        $this->categoryB = Category::factory()->forOrganization($this->orgB)->create(['name' => 'Cat B']);

        $this->tagA1 = Tag::factory()->forOrganization($this->orgA)->create(['name' => 'Tag A1']);
        $this->tagA2 = Tag::factory()->forOrganization($this->orgA)->create(['name' => 'Tag A2']);
        $this->tagB = Tag::factory()->forOrganization($this->orgB)->create(['name' => 'Tag B']);
    }

    public function test_associate_category_to_document(): void
    {
        $this->service->setCategory($this->documentA, $this->categoryA1);

        $this->assertEquals($this->categoryA1->id, $this->documentA->fresh()->category()->id);
    }

    public function test_replace_category(): void
    {
        $this->service->setCategory($this->documentA, $this->categoryA1);
        $this->assertEquals($this->categoryA1->id, $this->documentA->fresh()->category()->id);

        $this->service->setCategory($this->documentA, $this->categoryA2);

        $this->assertEquals($this->categoryA2->id, $this->documentA->fresh()->category()->id);
        $this->assertCount(1, $this->documentA->fresh()->categories);
    }

    public function test_remove_category(): void
    {
        $this->service->setCategory($this->documentA, $this->categoryA1);
        $this->assertEquals($this->categoryA1->id, $this->documentA->fresh()->category()->id);

        $this->service->setCategory($this->documentA, null);

        $this->assertNull($this->documentA->fresh()->category());
        $this->assertCount(0, $this->documentA->fresh()->categories);
    }

    public function test_cross_tenant_category_denied(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Category belongs to a different organization');

        $this->service->setCategory($this->documentA, $this->categoryB);
    }

    public function test_associate_multiple_tags(): void
    {
        $this->service->syncTags($this->documentA, [$this->tagA1->id, $this->tagA2->id]);

        $tags = $this->documentA->fresh()->tags;
        $this->assertCount(2, $tags);
        $this->assertTrue($tags->contains($this->tagA1));
        $this->assertTrue($tags->contains($this->tagA2));
    }

    public function test_replace_tags(): void
    {
        $this->service->syncTags($this->documentA, [$this->tagA1->id]);
        $this->assertCount(1, $this->documentA->fresh()->tags);

        $this->service->syncTags($this->documentA, [$this->tagA2->id]);

        $tags = $this->documentA->fresh()->tags;
        $this->assertCount(1, $tags);
        $this->assertTrue($tags->contains($this->tagA2));
        $this->assertFalse($tags->contains($this->tagA1));
    }

    public function test_remove_all_tags(): void
    {
        $this->service->syncTags($this->documentA, [$this->tagA1->id, $this->tagA2->id]);
        $this->assertCount(2, $this->documentA->fresh()->tags);

        $this->service->syncTags($this->documentA, []);

        $this->assertCount(0, $this->documentA->fresh()->tags);
    }

    public function test_cross_tenant_tag_denied(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('One or more tags belong to a different organization or do not exist');

        $this->service->syncTags($this->documentA, [$this->tagB->id]);
    }

    public function test_sync_tags_is_atomic_and_rejects_partial_updates(): void
    {
        // Document A -> Org A
        // Tag A1 -> Org A
        // Tag A2 -> Org A
        // Tag B -> Org B

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('One or more tags belong to a different organization or do not exist');

        // Trying to sync a mix of valid and invalid tags
        $this->service->syncTags($this->documentA, [$this->tagA1->id, $this->tagA2->id, $this->tagB->id]);
    }

    public function test_sync_tags_leaves_document_untouched_on_failure(): void
    {
        try {
            $this->service->syncTags($this->documentA, [$this->tagA1->id, $this->tagA2->id, $this->tagB->id]);
        } catch (HttpException $e) {
            // Expected
        }

        // Verify that NO tags were associated because the transaction rolled back
        $this->assertCount(0, $this->documentA->fresh()->tags);
    }

    public function test_sync_tags_leaves_existing_tags_intact_on_failure(): void
    {
        $this->service->syncTags($this->documentA, [$this->tagA1->id]);
        $this->assertCount(1, $this->documentA->fresh()->tags);

        try {
            $this->service->syncTags($this->documentA, [$this->tagA2->id, $this->tagB->id]);
        } catch (HttpException $e) {
            // Expected
        }

        // Verify that the original tag is still there, and NO new tags were added
        $tags = $this->documentA->fresh()->tags;
        $this->assertCount(1, $tags);
        $this->assertTrue($tags->contains($this->tagA1));
    }

    public function test_no_duplicates_in_pivot(): void
    {
        $this->service->setCategory($this->documentA, $this->categoryA1);

        $this->expectException(QueryException::class);

        // Manual insertion to bypass sync and test DB constraint
        DB::table('document_category')->insert([
            'document_id' => $this->documentA->id,
            'category_id' => $this->categoryA1->id,
        ]);
    }

    public function test_no_duplicates_in_tag_pivot(): void
    {
        $this->service->syncTags($this->documentA, [$this->tagA1->id]);

        $this->expectException(QueryException::class);

        // Manual insertion to bypass sync and test DB constraint
        DB::table('document_tag')->insert([
            'document_id' => $this->documentA->id,
            'tag_id' => $this->tagA1->id,
        ]);
    }

    public function test_document_from_another_organization_cannot_be_associated_with_category(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Category belongs to a different organization');

        $this->service->setCategory($this->documentB, $this->categoryA1);
    }

    public function test_document_from_another_organization_cannot_be_associated_with_tags(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('One or more tags belong to a different organization or do not exist');

        $this->service->syncTags($this->documentB, [$this->tagA1->id]);
    }

    public function test_soft_deleted_document_refuses_category_assignment(): void
    {
        $this->documentA->delete();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Document is deleted');

        $this->service->setCategory($this->documentA, $this->categoryA1);
    }

    public function test_soft_deleted_document_refuses_tag_sync(): void
    {
        $this->documentA->delete();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Document is deleted');

        $this->service->syncTags($this->documentA, [$this->tagA1->id]);
    }

    public function test_soft_deleted_category_refused(): void
    {
        $this->categoryA1->delete();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Category belongs to a different organization or is deleted');

        $this->service->setCategory($this->documentA, $this->categoryA1);
    }

    public function test_soft_deleted_tag_refused_in_sync_tags(): void
    {
        $this->tagA1->delete();

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('One or more tags belong to a different organization or do not exist');

        $this->service->syncTags($this->documentA, [$this->tagA1->id]);
    }

    public function test_category_and_tag_soft_delete_restore_lifecycle_with_document(): void
    {
        $this->service->setCategory($this->documentA, $this->categoryA1);
        $this->service->syncTags($this->documentA, [$this->tagA1->id]);

        $this->assertCount(1, $this->documentA->fresh()->categories);
        $this->assertCount(1, $this->documentA->fresh()->tags);

        // Soft delete category directly
        $this->categoryA1->delete();

        // Document still exists
        $this->assertDatabaseHas('documents', ['id' => $this->documentA->id]);

        // Trashed category cannot be assigned
        try {
            $this->service->setCategory($this->documentA, $this->categoryA1);
            $this->fail('Should not allow deleted category');
        } catch (HttpException $e) {
            // Expected
        }

        // Restore category
        $this->categoryA1->restore();

        // Can be assigned again
        $this->service->setCategory($this->documentA, $this->categoryA1);
        $this->assertEquals($this->categoryA1->id, $this->documentA->fresh()->category()->id);

        // Soft delete tag directly
        $this->tagA1->delete();

        // Document still exists
        $this->assertDatabaseHas('documents', ['id' => $this->documentA->id]);

        // Trashed tag cannot be synced
        try {
            $this->service->syncTags($this->documentA, [$this->tagA1->id]);
            $this->fail('Should not allow deleted tag');
        } catch (HttpException $e) {
            // Expected
        }

        // Restore tag
        $this->tagA1->restore();

        // Can be synced again
        $this->service->syncTags($this->documentA, [$this->tagA1->id]);
        $this->assertCount(1, $this->documentA->fresh()->tags);
    }
}
