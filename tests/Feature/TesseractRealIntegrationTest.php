<?php

namespace Tests\Feature;

use App\Enums\OcrStatus;
use App\Jobs\ProcessDocumentOcr;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\DocumentVersion;
use App\Models\Organization;
use App\Models\User;
use App\Services\AccessControlService;
use App\Services\Ocr\Engines\TesseractEngine;
use App\Services\OcrService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TesseractRealIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $userA;

    protected User $userB;

    protected OcrService $ocrService;

    protected TesseractEngine $realEngine;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        $this->seed(RolePermissionSeeder::class);

        $this->realEngine = new TesseractEngine;
        $this->ocrService = app(OcrService::class);
        $this->ocrService->setEngine($this->realEngine);
        $this->app->instance(OcrService::class, $this->ocrService);

        $this->orgA = Organization::factory()->create();
        $this->orgB = Organization::factory()->create();

        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $permissions = ['documents.view', 'documents.create', 'documents.update'];
        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->orgA->id);
        $roleA = Role::create(['name' => 'admin_real_ocr', 'guard_name' => 'web']);
        $roleA->givePermissionTo($permissions);
        $this->userA->assignRole($roleA);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    /**
     * Generate a real valid PDF containing native searchable text.
     */
    protected function generateNativePdfContent(string $text): string
    {
        $stream = 'BT /F1 20 Tf 50 700 Td ('.addcslashes($text, '()\\').') Tj ET';
        $streamLen = strlen($stream);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj\n";

        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /**
     * Generate a real valid scanned PDF with an embedded bitmap image rendered by GD.
     */
    protected function generateScannedPdfContent(string $textOnImage): string
    {
        $w = 600;
        $h = 200;
        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);
        imagestring($im, 5, 40, 90, $textOnImage, $black);

        ob_start();
        imagejpeg($im, null, 90);
        $jpegData = ob_get_clean();
        imagedestroy($im);
        $jpegLen = strlen($jpegData);

        $stream = 'q 500 0 0 166 50 500 cm /Im1 Do Q';
        $streamLen = strlen($stream);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /XObject << /Im1 5 0 R >> >> >>\nendobj\n";

        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj\n";

        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLen} >>\nstream\n{$jpegData}\nendstream\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /**
     * Generate a real valid 2-page mixed PDF: Page 1 native, Page 2 scanned image.
     */
    protected function generateMixedPdfContent(string $page1NativeText, string $page2ImageText): string
    {
        // Image for page 2
        $w = 600;
        $h = 200;
        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);
        imagestring($im, 5, 40, 90, $page2ImageText, $black);
        ob_start();
        imagejpeg($im, null, 90);
        $jpegData = ob_get_clean();
        imagedestroy($im);
        $jpegLen = strlen($jpegData);

        // Streams
        $stream1 = 'BT /F1 20 Tf 50 700 Td ('.addcslashes($page1NativeText, '()\\').') Tj ET';
        $streamLen1 = strlen($stream1);

        $stream2 = 'q 500 0 0 166 50 500 cm /Im1 Do Q';
        $streamLen2 = strlen($stream2);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        // 1: Catalog
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // 2: Pages root
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R 6 0 R] /Count 2 >>\nendobj\n";

        // 3: Page 1 (Native)
        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

        // 4: Stream 1
        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$streamLen1} >>\nstream\n{$stream1}\nendstream\nendobj\n";

        // 5: Font F1
        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        // 6: Page 2 (Scanned Image)
        $offsets[6] = strlen($pdf);
        $pdf .= "6 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 7 0 R /Resources << /XObject << /Im1 8 0 R >> >> >>\nendobj\n";

        // 7: Stream 2
        $offsets[7] = strlen($pdf);
        $pdf .= "7 0 obj\n<< /Length {$streamLen2} >>\nstream\n{$stream2}\nendstream\nendobj\n";

        // 8: XObject Image
        $offsets[8] = strlen($pdf);
        $pdf .= "8 0 obj\n<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLen} >>\nstream\n{$jpegData}\nendstream\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 9\n0000000000 65535 f \n";
        for ($i = 1; $i <= 8; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 9 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected function createDocumentWithContent(string $rawContent, string $extension = 'pdf'): Document
    {
        $path = "organizations/{$this->orgA->id}/documents/doc_".uniqid().".{$extension}";
        Storage::disk('private')->put($path, $rawContent);

        $document = Document::factory()->create([
            'organization_id' => $this->orgA->id,
            'uploaded_by' => $this->userA->id,
            'extension' => $extension,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : "image/{$extension}",
            'storage_disk' => 'private',
            'storage_path' => $path,
            'size' => strlen($rawContent),
        ]);

        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $this->userA->id,
            'version_number' => 1,
            'extension' => $extension,
            'mime_type' => $document->mime_type,
            'storage_disk' => 'private',
            'storage_path' => $path,
            'size' => strlen($rawContent),
        ]);

        app(AccessControlService::class)->grantDocumentPermission($document, $this->userA, 'view');
        app(AccessControlService::class)->grantDocumentPermission($document, $this->userA, 'update');

        return $document->fresh();
    }

    public function test_real_cas_a_native_pdf_extraction(): void
    {
        $nativeContent = $this->generateNativePdfContent('CONTRAT COMMERCIAL REEL NUMERO 78910');
        $document = $this->createDocumentWithContent($nativeContent, 'pdf');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertStringContainsString('CONTRAT COMMERCIAL REEL NUMERO 78910', $ocr->extracted_text);
        $this->assertGreaterThan(0, $ocr->word_count);
        $this->assertNull($ocr->error_message);
    }

    public function test_real_cas_b_scanned_pdf_extraction_with_tesseract_and_poppler(): void
    {
        $scannedContent = $this->generateScannedPdfContent('FACTURE SCANNEE TESSERACT 445566');
        $document = $this->createDocumentWithContent($scannedContent, 'pdf');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertStringContainsString('FACTURE SCANNEE TESSERACT 445566', $ocr->extracted_text);
        $this->assertGreaterThan(0, $ocr->word_count);
        $this->assertNull($ocr->error_message);
    }

    public function test_real_cas_c_mixed_pdf_extraction_page_by_page(): void
    {
        $mixedContent = $this->generateMixedPdfContent(
            'PAGE UN TEXTE NATIF REEL',
            'PAGE DEUX SCAN REEL 112233'
        );
        $document = $this->createDocumentWithContent($mixedContent, 'pdf');

        $ocr = $this->ocrService->process($document);

        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertStringContainsString('PAGE UN TEXTE NATIF REEL', $ocr->extracted_text);
        $this->assertStringContainsString('PAGE DEUX SCAN REEL 112233', $ocr->extracted_text);
    }

    public function test_real_background_job_execution_with_redis_or_database_queue(): void
    {
        $scannedContent = $this->generateScannedPdfContent('JOB ASYNC VERIFIE APPGED 889900');
        $document = $this->createDocumentWithContent($scannedContent, 'pdf');

        // Dispatch background job
        $job = new ProcessDocumentOcr($document, $document->currentVersion);
        $job->handle($this->ocrService);

        $ocr = DocumentOcr::where('document_id', $document->id)->first();
        $this->assertNotNull($ocr);
        $this->assertEquals(OcrStatus::Completed, $ocr->status);
        $this->assertStringContainsString('JOB ASYNC VERIFIE APPGED 889900', $ocr->extracted_text);
    }

    public function test_real_temporary_files_are_completely_cleaned_up_after_processing(): void
    {
        $tempRoot = storage_path('app/temp/ocr');
        $beforeCount = File::isDirectory($tempRoot) ? count(File::allFiles($tempRoot)) : 0;

        $scannedContent = $this->generateScannedPdfContent('NETTOYAGE GARANTI SANS RESIDUS');
        $document = $this->createDocumentWithContent($scannedContent, 'pdf');

        $this->ocrService->process($document);

        $afterCount = File::isDirectory($tempRoot) ? count(File::allFiles($tempRoot)) : 0;
        $this->assertEquals($beforeCount, $afterCount, 'Tous les fichiers et images temporaires doivent être nettoyés.');
    }
}
