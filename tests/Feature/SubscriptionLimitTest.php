<?php

namespace Tests\Feature;

use App\Enums\FolderType;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Services\BillingService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLimitTest extends TestCase
{
    use RefreshDatabase;

    protected BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
        $this->billingService = app(BillingService::class);
    }

    public function test_user_limit_enforcement(): void
    {
        $org = Organization::factory()->create(); // On Essential plan (5 users max)

        // Create 5 users
        User::factory()->count(5)->create(['organization_id' => $org->id]);

        $this->assertTrue($this->billingService->canAddUser(0, $org));
        $this->assertFalse($this->billingService->canAddUser(1, $org));

        $this->expectException(SubscriptionLimitExceededException::class);
        $this->expectExceptionMessage("Votre abonnement Essentiel autorise jusqu'à 5 utilisateurs. Passez au plan supérieur pour ajouter davantage d'utilisateurs.");

        $this->billingService->assertCanAddUser(1, $org);
    }

    public function test_storage_limit_enforcement(): void
    {
        $org = Organization::factory()->create(); // 20 GB max (21_474_836_480 bytes)
        $limitBytes = 20 * 1024 * 1024 * 1024;

        // Current size: 19 GB
        Document::factory()->create([
            'organization_id' => $org->id,
            'size' => 19 * 1024 * 1024 * 1024,
            'status' => 'published',
        ]);

        // Adding 500 MB is fine (19.5 GB <= 20 GB)
        $this->assertTrue($this->billingService->canAddStorage(500 * 1024 * 1024, $org));

        // Adding 2 GB exceeds limit (21 GB > 20 GB)
        $this->assertFalse($this->billingService->canAddStorage(2 * 1024 * 1024 * 1024, $org));

        $this->expectException(SubscriptionLimitExceededException::class);
        $this->billingService->assertCanAddStorage(2 * 1024 * 1024 * 1024, $org);
    }

    public function test_direction_limit_enforcement(): void
    {
        $org = Organization::factory()->create(); // 3 directions max

        Folder::factory()->count(3)->create([
            'organization_id' => $org->id,
            'folder_type' => FolderType::Department,
        ]);

        $this->assertFalse($this->billingService->canAddDirection(1, $org));

        $this->expectException(SubscriptionLimitExceededException::class);
        $this->expectExceptionMessage("Votre abonnement Essentiel autorise jusqu'à 3 directions.");

        $this->billingService->assertCanAddDirection(1, $org);
    }

    public function test_document_type_limit_enforcement(): void
    {
        $org = Organization::factory()->create(); // 15 doc types max

        Folder::factory()->count(15)->create([
            'organization_id' => $org->id,
            'folder_type' => FolderType::DocumentType,
        ]);

        $this->assertFalse($this->billingService->canAddDocumentType(1, $org));

        $this->expectException(SubscriptionLimitExceededException::class);
        $this->expectExceptionMessage("Votre abonnement Essentiel autorise jusqu'à 15 types documentaires.");

        $this->billingService->assertCanAddDocumentType(1, $org);
    }

    public function test_ocr_limit_enforcement(): void
    {
        $org = Organization::factory()->create(); // 100 pages max
        $doc = Document::factory()->create(['organization_id' => $org->id]);

        DocumentOcr::factory()->count(100)->create([
            'organization_id' => $org->id,
            'document_id' => $doc->id,
            'created_at' => now(),
        ]);

        $this->assertFalse($this->billingService->canProcessOcrPages(1, $org));

        $this->expectException(SubscriptionLimitExceededException::class);
        $this->expectExceptionMessage('Votre quota de pages OCR pour ce mois (100 pages) est atteint pour le plan Essentiel.');

        $this->billingService->assertCanProcessOcr(1, $org);
    }

    public function test_downgrade_does_not_destroy_data_and_blocks_further_additions(): void
    {
        // 1. Organization on Professional plan with 18 users
        $org = Organization::factory()->create();
        $profPlan = Plan::where('slug', 'professional')->firstOrFail();
        $essentialPlan = Plan::where('slug', 'essential')->firstOrFail();

        $this->billingService->changePlan($org, $profPlan, 'monthly');
        User::factory()->count(18)->create(['organization_id' => $org->id]);

        $this->assertDatabaseCount('users', 18);

        // 2. Downgrade to Essential (5 users limit)
        $this->billingService->changePlan($org, $essentialPlan, 'monthly');

        // Verify: NO users deleted!
        $this->assertDatabaseCount('users', 18);

        // Usage reflects exceeded state
        $usage = $this->billingService->getUsage($org);
        $this->assertTrue($usage['is_any_exceeded']);
        $this->assertTrue($usage['metrics']['users']['is_exceeded']);
        $this->assertSame(18, $usage['metrics']['users']['used']);
        $this->assertSame(5, $usage['metrics']['users']['limit']);

        // Adding another user is blocked
        $this->assertFalse($this->billingService->canAddUser(1, $org));
    }
}
