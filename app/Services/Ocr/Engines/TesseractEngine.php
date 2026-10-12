<?php

namespace App\Services\Ocr\Engines;

use App\Services\Ocr\Contracts\OcrEngineInterface;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class TesseractEngine implements OcrEngineInterface
{
    public function __construct(
        protected ?string $binaryPath = null,
        protected ?string $pdftoppmPath = null,
        protected ?string $pdftotextPath = null,
        protected ?string $pdfinfoPath = null
    ) {
        $this->binaryPath = $this->binaryPath ?? config('ocr.binary_path', 'tesseract');
        $this->pdftoppmPath = $this->pdftoppmPath ?? config('ocr.pdftoppm_path', 'pdftoppm');
        $this->pdftotextPath = $this->pdftotextPath ?? config('ocr.pdftotext_path', 'pdftotext');
        $this->pdfinfoPath = $this->pdfinfoPath ?? config('ocr.pdfinfo_path', 'pdfinfo');
    }

    /**
     * Resolve the effective Tesseract executable path.
     */
    public function resolveTesseractPath(): string
    {
        if ($this->binaryPath && $this->binaryPath !== 'tesseract') {
            return $this->binaryPath;
        }

        try {
            if (Process::timeout(3)->run([$this->binaryPath, '--version'])->successful()) {
                return $this->binaryPath;
            }
        } catch (\Throwable) {
        }

        $fallbacks = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
        ];

        foreach ($fallbacks as $fallback) {
            if (file_exists($fallback)) {
                return $fallback;
            }
        }

        return $this->binaryPath;
    }

    /**
     * Resolve the effective pdftoppm executable path.
     */
    public function resolvePdftoppmPath(): string
    {
        if ($this->pdftoppmPath && $this->pdftoppmPath !== 'pdftoppm') {
            return $this->pdftoppmPath;
        }

        try {
            if (Process::timeout(3)->run([$this->pdftoppmPath, '-v'])->successful()) {
                return $this->pdftoppmPath;
            }
        } catch (\Throwable) {
        }

        $fallbacks = [
            'C:\\poppler-26.09.0\\Library\\bin\\pdftoppm.exe',
            '/usr/bin/pdftoppm',
            '/usr/local/bin/pdftoppm',
        ];

        foreach ($fallbacks as $fallback) {
            if (file_exists($fallback)) {
                return $fallback;
            }
        }

        return $this->pdftoppmPath;
    }

    /**
     * Resolve the effective pdftotext executable path.
     */
    public function resolvePdftotextPath(): string
    {
        if ($this->pdftotextPath && $this->pdftotextPath !== 'pdftotext') {
            return $this->pdftotextPath;
        }

        try {
            if (Process::timeout(3)->run([$this->pdftotextPath, '-v'])->successful()) {
                return $this->pdftotextPath;
            }
        } catch (\Throwable) {
        }

        $fallbacks = [
            'C:\\poppler-26.09.0\\Library\\bin\\pdftotext.exe',
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
        ];

        foreach ($fallbacks as $fallback) {
            if (file_exists($fallback)) {
                return $fallback;
            }
        }

        return $this->pdftotextPath;
    }

    /**
     * Resolve the effective pdfinfo executable path.
     */
    public function resolvePdfinfoPath(): string
    {
        if ($this->pdfinfoPath && $this->pdfinfoPath !== 'pdfinfo') {
            return $this->pdfinfoPath;
        }

        try {
            if (Process::timeout(3)->run([$this->pdfinfoPath, '-v'])->successful()) {
                return $this->pdfinfoPath;
            }
        } catch (\Throwable) {
        }

        $fallbacks = [
            'C:\\poppler-26.09.0\\Library\\bin\\pdfinfo.exe',
            '/usr/bin/pdfinfo',
            '/usr/local/bin/pdfinfo',
        ];

        foreach ($fallbacks as $fallback) {
            if (file_exists($fallback)) {
                return $fallback;
            }
        }

        return $this->pdfinfoPath;
    }

    /**
     * Check whether the Tesseract binary is executable on the host system.
     */
    public function isAvailable(): bool
    {
        try {
            $resolved = $this->resolveTesseractPath();
            $result = Process::timeout(5)->run([$resolved, '--version']);

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check whether a specific binary is executable with a test flag.
     *
     * @param  array<string>  $args
     */
    public function isBinaryExecutable(string $binary, array $args = ['-v']): bool
    {
        try {
            $result = Process::timeout(3)->run(array_merge([$binary], $args));

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Determine the total number of pages in a PDF document.
     */
    public function getPageCount(string $filePath): int
    {
        $pdfinfo = $this->resolvePdfinfoPath();

        try {
            $result = Process::timeout(5)->run([$pdfinfo, $filePath]);
            if ($result->successful() && preg_match('/Pages:\s+(\d+)/i', $result->output(), $matches)) {
                return max(1, (int) $matches[1]);
            }
        } catch (\Throwable) {
        }

        // Structural fallback for minimal or non-poppler environments
        try {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                if (preg_match('/\/Type\s*\/Pages[^>]*\/Count\s+(\d+)/i', $content, $m)) {
                    return max(1, (int) $m[1]);
                }
                $matches = preg_match_all('/\/Type\s*\/Page\b/i', $content);
                if ($matches > 0) {
                    return $matches;
                }
            }
        } catch (\Throwable) {
        }

        return 1;
    }

    /**
     * Extract text from the given file using native tools or Tesseract OCR.
     */
    public function extractText(string $filePath, string $extension, array $options = []): string
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("Fichier introuvable pour le traitement OCR : {$filePath}");
        }

        $extension = strtolower(ltrim($extension, '.'));

        if ($extension === 'pdf') {
            return $this->processPdf($filePath, $options);
        }

        return $this->processImage($filePath, $options);
    }

    /**
     * Process image files (PNG, JPG, WEBP) using Tesseract directly.
     */
    protected function processImage(string $filePath, array $options = []): string
    {
        if (! $this->isAvailable()) {
            $resolved = $this->resolveTesseractPath();

            throw new RuntimeException("Le moteur OCR Tesseract n'est pas installé ou n'est pas disponible sur le système ({$resolved}).");
        }

        $languages = $options['languages'] ?? config('ocr.languages', ['fra', 'eng']);
        $langParam = is_array($languages) ? implode('+', $languages) : (string) $languages;
        $timeout = (int) ($options['timeout'] ?? config('ocr.timeout', 120));

        $tesseract = $this->resolveTesseractPath();

        try {
            $result = Process::timeout($timeout)->run([
                $tesseract,
                $filePath,
                'stdout',
                '-l',
                $langParam,
            ]);
        } catch (ProcessTimedOutException $e) {
            throw new RuntimeException("Délai maximal dépassé lors de la reconnaissance OCR de l'image ({$timeout}s).", previous: $e);
        }

        if (! $result->successful()) {
            $error = $result->errorOutput() ?: $result->output();

            throw new RuntimeException("Échec de l'extraction OCR Tesseract : {$error}");
        }

        $text = $this->cleanExtractedText($result->output());

        if (empty($text)) {
            throw new RuntimeException("Aucun texte exploitable n'a été reconnu dans l'image.");
        }

        return $text;
    }

    /**
     * Process PDF files handling Native (Cas A), Scanned (Cas B) and Mixed (Cas C) documents page-by-page.
     */
    protected function processPdf(string $filePath, array $options = []): string
    {
        $languages = $options['languages'] ?? config('ocr.languages', ['fra', 'eng']);
        $langParam = is_array($languages) ? implode('+', $languages) : (string) $languages;
        $maxPages = (int) ($options['max_pages'] ?? config('ocr.max_pages', 50));
        $dpi = (int) ($options['dpi'] ?? config('ocr.dpi', 200));
        $nativeTimeout = (int) ($options['native_timeout'] ?? config('ocr.native_timeout', 15));
        $conversionTimeout = (int) ($options['conversion_timeout'] ?? config('ocr.conversion_timeout', 60));
        $ocrTimeout = (int) ($options['timeout'] ?? config('ocr.timeout', 120));
        $minNativeLength = (int) ($options['min_native_text_length'] ?? config('ocr.min_native_text_length', 15));

        $pageCount = $this->getPageCount($filePath);
        if ($pageCount > $maxPages) {
            throw new RuntimeException("Le document PDF dépasse la limite de {$maxPages} pages autorisées pour l'OCR (document : {$pageCount} pages).");
        }

        $pdftotext = $this->resolvePdftotextPath();
        $pdftoppm = $this->resolvePdftoppmPath();
        $tesseract = $this->resolveTesseractPath();

        $isPdftotextAvailable = $this->isBinaryExecutable($pdftotext, ['-v']);
        $isPdftoppmAvailable = $this->isBinaryExecutable($pdftoppm, ['-v']);
        $isTesseractAvailable = $this->isAvailable();

        // Create isolated unique temporary directory for this PDF run
        $tempDir = storage_path('app/temp/ocr/job_'.Str::uuid());
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $pageTexts = [];

        try {
            for ($page = 1; $page <= $pageCount; $page++) {
                $nativeExtracted = '';

                // Step 1: Try native text extraction for page $page
                if ($isPdftotextAvailable) {
                    try {
                        $nativeResult = Process::timeout($nativeTimeout)->run([
                            $pdftotext,
                            '-f', (string) $page,
                            '-l', (string) $page,
                            '-enc', 'UTF-8',
                            $filePath,
                            '-',
                        ]);

                        if ($nativeResult->successful()) {
                            $nativeExtracted = $this->cleanExtractedText($nativeResult->output());
                        }
                    } catch (\Throwable) {
                    }
                }

                // Check if native text on this page is significant
                $alphanumericContent = preg_replace('/[\s\p{C}]+/u', '', $nativeExtracted);
                if (mb_strlen($alphanumericContent) >= $minNativeLength) {
                    $pageTexts[$page] = $nativeExtracted;

                    continue;
                }

                // Step 2: Page is scanned or lacks native text -> Rasterize page & OCR with Tesseract
                if (! $isPdftoppmAvailable) {
                    throw new RuntimeException("L'utilitaire Poppler pdftoppm n'est pas disponible pour rasteriser les pages numérisées.");
                }

                if (! $isTesseractAvailable) {
                    throw new RuntimeException("Le moteur OCR Tesseract n'est pas disponible pour analyser les pages numérisées.");
                }

                $pagePrefix = $tempDir.DIRECTORY_SEPARATOR."page_{$page}";

                try {
                    $convertResult = Process::timeout($conversionTimeout)->run([
                        $pdftoppm,
                        '-png',
                        '-r', (string) $dpi,
                        '-f', (string) $page,
                        '-l', (string) $page,
                        $filePath,
                        $pagePrefix,
                    ]);
                } catch (ProcessTimedOutException $e) {
                    throw new RuntimeException("Délai de conversion PDF vers image dépassé sur la page {$page} ({$conversionTimeout}s).", previous: $e);
                }

                if (! $convertResult->successful()) {
                    $err = $convertResult->errorOutput() ?: $convertResult->output();

                    throw new RuntimeException("Échec de la conversion de la page {$page} du PDF en image : {$err}");
                }

                $images = File::glob($pagePrefix.'*.png');
                if (empty($images)) {
                    continue;
                }

                $pageOcrTexts = [];
                foreach ($images as $imagePath) {
                    try {
                        $ocrResult = Process::timeout($ocrTimeout)->run([
                            $tesseract,
                            $imagePath,
                            'stdout',
                            '-l',
                            $langParam,
                        ]);

                        if (! $ocrResult->successful()) {
                            $err = $ocrResult->errorOutput() ?: $ocrResult->output();

                            throw new RuntimeException("Échec de la reconnaissance OCR Tesseract sur la page {$page} : {$err}");
                        }

                        $text = $this->cleanExtractedText($ocrResult->output());
                        if (! empty($text)) {
                            $pageOcrTexts[] = $text;
                        }
                    } catch (ProcessTimedOutException $e) {
                        throw new RuntimeException("Délai de reconnaissance OCR Tesseract dépassé sur la page {$page}.", previous: $e);
                    } finally {
                        // Immediately delete the page image to free disk space
                        if (File::exists($imagePath)) {
                            File::delete($imagePath);
                        }
                    }
                }

                if (! empty($pageOcrTexts)) {
                    $pageTexts[$page] = implode("\n", $pageOcrTexts);
                }
            }
        } finally {
            // Guaranteed cleanup of temp directory and all intermediate files
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }

        $allText = trim(implode("\n\n", array_filter($pageTexts)));

        if (empty($allText)) {
            throw new RuntimeException("Aucun texte exploitable n'a pu être extrait ou reconnu dans le document.");
        }

        return $allText;
    }

    /**
     * Clean and normalize extracted text (remove null bytes and non-printable control characters).
     */
    public function cleanExtractedText(string $text): string
    {
        // Remove null bytes and non-printable control chars except newlines and tabs
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Normalize UTF-8
        if (! mb_check_encoding($cleaned, 'UTF-8')) {
            $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');
        }

        return trim($cleaned);
    }
}
