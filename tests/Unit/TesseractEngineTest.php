<?php

namespace Tests\Unit;

use App\Services\Ocr\Engines\TesseractEngine;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class TesseractEngineTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tesseract_unit_test_'.uniqid();
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    protected function createDummyFile(string $extension, string $content = 'dummy'): string
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.'file_'.uniqid().'.'.$extension;
        file_put_contents($path, $content);

        return $path;
    }

    public function test_extract_text_throws_if_file_does_not_exist(): void
    {
        $engine = new TesseractEngine;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fichier introuvable pour le traitement OCR');

        $engine->extractText('/non/existent/path/file.pdf', 'pdf');
    }

    public function test_extract_image_throws_if_tesseract_not_available(): void
    {
        $engine = new TesseractEngine(binaryPath: 'missing_tesseract_binary');
        $file = $this->createDummyFile('png');

        Process::fake([
            '*missing_tesseract_binary*' => Process::result(exitCode: 1),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Le moteur OCR Tesseract n'est pas installé ou n'est pas disponible");

        $engine->extractText($file, 'png');
    }

    public function test_extract_image_success_with_tesseract(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('png');

        Process::fake([
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*stdout*' => Process::result("Facture payée 1500 EUR\n"),
        ]);

        $text = $engine->extractText($file, 'png');

        $this->assertEquals('Facture payée 1500 EUR', $text);
    }

    public function test_extract_image_throws_when_tesseract_fails(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('jpg');

        Process::fake([
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*stdout*' => Process::result(errorOutput: 'Erreur segmentation', exitCode: 1),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Échec de l'extraction OCR Tesseract : Erreur segmentation");

        $engine->extractText($file, 'jpg');
    }

    public function test_extract_image_throws_when_text_is_empty(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('png');

        Process::fake([
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*stdout*' => Process::result("   \n\t  "),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Aucun texte exploitable n'a été reconnu dans l'image.");

        $engine->extractText($file, 'png');
    }

    public function test_pdf_native_extraction_cas_a_uses_pdftotext(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('pdf');

        Process::fake([
            '*pdfinfo*' => Process::result("Pages: 1\n"),
            '*' => function ($process) {
                $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
                if (str_contains($cmd, '-v') || str_contains($cmd, '--version')) {
                    return Process::result("version ok\n");
                }
                if (str_contains($cmd, 'pdftotext')) {
                    return Process::result('Texte natif du contrat PDF très clair et lisible');
                }

                return Process::result();
            },
        ]);

        $text = $engine->extractText($file, 'pdf');

        $this->assertEquals('Texte natif du contrat PDF très clair et lisible', $text);
        Process::assertRan(function ($process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, 'pdftotext');
        });
        Process::assertNotRan(function ($process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, '-png');
        });
    }

    public function test_pdf_scanned_extraction_cas_b_uses_pdftoppm_and_tesseract(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('pdf');

        Process::fake([
            '*pdfinfo*' => Process::result("Pages: 1\n"),
            '*-v*' => Process::result("version 26.0\n"),
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*pdftoppm*' => function ($process) {
                $args = is_array($process->command) ? $process->command : explode(' ', $process->command);
                $prefix = end($args);
                file_put_contents($prefix.'-1.png', 'fake image bytes');

                return Process::result();
            },
            '*tesseract*' => function ($process) {
                $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
                if (str_contains($cmd, '--version')) {
                    return Process::result("tesseract 5.5.0\n");
                }

                return Process::result("Texte OCR reconnu sur le scan numérisé\n");
            },
            '*pdftotext*' => Process::result(''),
        ]);

        $text = $engine->extractText($file, 'pdf');

        $this->assertEquals('Texte OCR reconnu sur le scan numérisé', $text);
        Process::assertRan(function ($process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, 'pdftoppm');
        });
        Process::assertRan(function ($process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($cmd, 'tesseract');
        });
    }

    public function test_pdf_mixed_extraction_cas_c_combines_native_and_ocr_pages(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('pdf');

        Process::fake([
            '*pdfinfo*' => Process::result("Pages: 2\n"),
            '*-v*' => Process::result("version 26.0\n"),
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*pdftotext*' => function ($process) {
                $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
                if (str_contains($cmd, '-f 1')) {
                    return Process::result('Page 1: Page de garde avec texte natif sélectionnable');
                }

                return Process::result('');
            },
            '*pdftoppm*' => function ($process) {
                $args = is_array($process->command) ? $process->command : explode(' ', $process->command);
                $prefix = end($args);
                file_put_contents($prefix.'-2.png', 'fake image bytes');

                return Process::result();
            },
            '*tesseract*' => function ($process) {
                $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
                if (str_contains($cmd, '--version')) {
                    return Process::result("tesseract 5.5.0\n");
                }

                return Process::result("Page 2: Annexe manuscrite extraite par OCR\n");
            },
        ]);

        $text = $engine->extractText($file, 'pdf');

        $this->assertStringContainsString('Page 1: Page de garde avec texte natif sélectionnable', $text);
        $this->assertStringContainsString('Page 2: Annexe manuscrite extraite par OCR', $text);
    }

    public function test_pdf_exceeding_max_pages_limit_throws_exception(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('pdf');

        Process::fake([
            '*pdfinfo*' => Process::result("Pages: 55\n"),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Le document PDF dépasse la limite de 50 pages');

        $engine->extractText($file, 'pdf', ['max_pages' => 50]);
    }

    public function test_pdf_scanned_throws_if_no_text_recognized(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('pdf');

        Process::fake([
            '*pdfinfo*' => Process::result("Pages: 1\n"),
            '*-v*' => Process::result("version 26.0\n"),
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*pdftotext*' => Process::result(''),
            '*pdftoppm*' => function ($process) {
                $args = is_array($process->command) ? $process->command : explode(' ', $process->command);
                $prefix = end($args);
                file_put_contents($prefix.'-1.png', 'fake image bytes');

                return Process::result();
            },
            '*tesseract*' => function ($process) {
                $cmd = is_array($process->command) ? implode(' ', $process->command) : $process->command;
                if (str_contains($cmd, '--version')) {
                    return Process::result("tesseract 5.5.0\n");
                }

                return Process::result('');
            },
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Aucun texte exploitable n'a pu être extrait ou reconnu dans le document.");

        $engine->extractText($file, 'pdf');
    }

    public function test_pdf_throws_if_pdftoppm_fails(): void
    {
        $engine = new TesseractEngine;
        $file = $this->createDummyFile('pdf');

        Process::fake([
            '*pdfinfo*' => Process::result("Pages: 1\n"),
            '*-v*' => Process::result("version 26.0\n"),
            '*--version*' => Process::result("tesseract 5.5.0\n"),
            '*pdftotext*' => Process::result(''),
            '*pdftoppm*' => Process::result(errorOutput: 'PDF corrompu', exitCode: 1),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Échec de la conversion de la page 1 du PDF en image : PDF corrompu');

        $engine->extractText($file, 'pdf');
    }

    public function test_clean_extracted_text_removes_control_characters_and_normalizes_utf8(): void
    {
        $engine = new TesseractEngine;

        $dirty = "Hello\x00\x01\x02World\t\nValid text\x7F";
        $cleaned = $engine->cleanExtractedText($dirty);

        $this->assertEquals("HelloWorld\t\nValid text", $cleaned);
    }
}
