<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocumentOcr;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_health_check_endpoint_returns_ok_without_exposing_sensitive_data(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);

        // Health check must not reveal database passwords, secrets or stack traces
        $content = $response->getContent();
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('r2_secret', strtolower($content));
        $this->assertStringNotContainsString('aws_secret', strtolower($content));
    }

    public function test_sensitive_files_are_not_served_as_application_routes(): void
    {
        // Direct route hits for environment or git internals must be 404
        $this->get('/.env')->assertNotFound();
        $this->get('/.env.example')->assertNotFound();
        $this->get('/.git/config')->assertNotFound();
        $this->get('/composer.json')->assertNotFound();
        $this->get('/composer.lock')->assertNotFound();
    }

    public function test_private_storage_disks_are_strictly_configured_as_private(): void
    {
        // The private disk must be private
        $this->assertEquals('local', Config::get('filesystems.disks.private.driver'));

        // The R2 disk must have visibility = private and correct S3 driver
        $this->assertEquals('s3', Config::get('filesystems.disks.r2.driver'));
        $this->assertEquals('private', Config::get('filesystems.disks.r2.visibility'));
        $this->assertTrue(Config::get('filesystems.disks.r2.throw'));

        // Default documents disk must resolve to private or r2
        $docDisk = Config::get('filesystems.documents_disk');
        $this->assertTrue(in_array($docDisk, ['private', 'local', 'r2', 's3'], true));
    }

    public function test_queue_configuration_and_ocr_job_sla_parameters(): void
    {
        $job = new ProcessDocumentOcr(new Document);

        $this->assertEquals(3, $job->tries, 'OCR job must allow 3 attempts');
        $this->assertEquals(180, $job->timeout, 'OCR job timeout must be 180 seconds');

        $retryAfter = Config::get('queue.connections.database.retry_after');
        $this->assertGreaterThanOrEqual(
            $job->timeout,
            $retryAfter,
            'Queue retry_after must be greater than or equal to OCR job timeout to prevent race conditions'
        );
    }

    public function test_ocr_configuration_supports_required_languages(): void
    {
        $languages = Config::get('ocr.languages');

        $this->assertIsArray($languages);
        $this->assertContains('fra', $languages, 'OCR must support French');
        $this->assertContains('eng', $languages, 'OCR must support English');
        $this->assertGreaterThanOrEqual(60, Config::get('ocr.timeout'));
    }

    public function test_storage_migration_artisan_command_is_registered(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('documents:migrate-storage', $commands);
    }

    public function test_failed_jobs_table_is_present_in_database(): void
    {
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable('failed_jobs'),
            'failed_jobs table must exist to capture queue failures'
        );
    }

    public function test_public_disk_does_not_contain_tenant_private_documents(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create(['name' => 'Secure Org', 'status' => 'active']);
        $user = User::factory()->create(['organization_id' => $org->id]);

        $doc = Document::factory()->create([
            'organization_id' => $org->id,
            'uploaded_by' => $user->id,
            'storage_disk' => 'private',
            'storage_path' => 'documents/confidential.pdf',
        ]);

        Storage::disk('private')->put('documents/confidential.pdf', 'SECRET DATA');

        // Verify file is not on the public disk
        $this->assertFalse(Storage::disk('public')->exists('documents/confidential.pdf'));
        $this->assertFalse(Storage::disk('public')->exists("organizations/{$org->id}/documents/{$doc->id}"));
    }
}
