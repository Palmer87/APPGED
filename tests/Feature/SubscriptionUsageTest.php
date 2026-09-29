<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\User;
use App\Services\SubscriptionUsageService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionUsageTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionUsageService $usageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
        $this->usageService = app(SubscriptionUsageService::class);
    }

    public function test_computes_exact_usage_and_percentages(): void
    {
        $org = Organization::factory()->create();

        // 1. Create 3 users in org
        User::factory()->count(3)->create(['organization_id' => $org->id]);

        // 2. Create 2 departments and 4 document types
        Folder::factory()->count(2)->create([
            'organization_id' => $org->id,
            'folder_type' => FolderType::Department,
        ]);
        Folder::factory()->count(4)->create([
            'organization_id' => $org->id,
            'folder_type' => FolderType::DocumentType,
        ]);

        // 3. Create document with size (10 MB = 10485760 bytes)
        $doc = Document::factory()->create([
            'organization_id' => $org->id,
            'size' => 10485760,
            'status' => 'published',
        ]);

        // 4. Create 5 OCR runs
        DocumentOcr::factory()->count(5)->create([
            'organization_id' => $org->id,
            'document_id' => $doc->id,
            'created_at' => now(),
        ]);

        $usage = $this->usageService->getUsage($org);

        $this->assertSame('Essentiel', $usage['plan_name']);
        $this->assertFalse($usage['is_any_exceeded']);

        // Users: 3 used / 5 limit = 60%
        $this->assertSame(3, $usage['metrics']['users']['used']);
        $this->assertSame(5, $usage['metrics']['users']['limit']);
        $this->assertEquals(60.0, $usage['metrics']['users']['percentage']);

        // Directions: 2 used / 3 limit
        $this->assertSame(2, $usage['metrics']['directions']['used']);
        $this->assertSame(3, $usage['metrics']['directions']['limit']);

        // Document types: 4 used / 15 limit
        $this->assertSame(4, $usage['metrics']['document_types']['used']);
        $this->assertSame(15, $usage['metrics']['document_types']['limit']);

        // OCR: 5 used / 100 limit = 5%
        $this->assertSame(5, $usage['metrics']['ocr']['used']);
        $this->assertSame(100, $usage['metrics']['ocr']['limit']);
        $this->assertEquals(5.0, $usage['metrics']['ocr']['percentage']);

        // Storage
        $this->assertSame(10485760, $usage['metrics']['storage']['used_bytes']);
        $this->assertSame('10 Mo', $usage['metrics']['storage']['used_formatted']);
    }

    public function test_warns_when_reaching_80_percent_of_limit(): void
    {
        $org = Organization::factory()->create();

        // 4 users out of 5 = 80%
        User::factory()->count(4)->create(['organization_id' => $org->id]);

        $usage = $this->usageService->getUsage($org);

        $this->assertNotEmpty($usage['warnings']);
        $this->assertSame('users', $usage['warnings'][0]['type']);
    }
}
