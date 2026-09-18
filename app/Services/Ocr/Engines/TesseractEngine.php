<?php

namespace App\Services\Ocr\Engines;

use App\Services\Ocr\Contracts\OcrEngineInterface;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class TesseractEngine implements OcrEngineInterface
{
    public function __construct(
        protected ?string $binaryPath = null
    ) {
        $this->binaryPath = $this->binaryPath ?? config('ocr.binary_path', 'tesseract');
    }

    /**
     * Check whether the Tesseract binary is executable on the host system.
     */
    public function isAvailable(): bool
    {
        try {
            $result = Process::timeout(5)->run([$this->binaryPath, '--version']);

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Extract text from the given file using Tesseract or native PDF extraction.
     */
    public function extractText(string $filePath, string $extension, array $options = []): string
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("Fichier introuvable pour le traitement OCR : {$filePath}");
        }

        $extension = strtolower(ltrim($extension, '.'));

        // For PDF: check if native text is present to avoid heavy OCR
        if ($extension === 'pdf') {
            $nativeText = $this->extractNativePdfText($filePath);
            if (! empty(trim($nativeText)) && mb_strlen(trim($nativeText)) >= 20) {
                return trim($nativeText);
            }
        }

        if (! $this->isAvailable()) {
            throw new RuntimeException("Le moteur OCR Tesseract n'est pas installé ou n'est pas disponible sur le système ({$this->binaryPath}).");
        }

        $languages = $options['languages'] ?? config('ocr.languages', ['fra', 'eng']);
        $langParam = is_array($languages) ? implode('+', $languages) : (string) $languages;
        $timeout = (int) ($options['timeout'] ?? config('ocr.timeout', 120));

        // Command: tesseract <filePath> stdout -l <langParam>
        $command = [
            $this->binaryPath,
            $filePath,
            'stdout',
            '-l',
            $langParam,
        ];

        $result = Process::timeout($timeout)->run($command);

        if (! $result->successful()) {
            $error = $result->errorOutput() ?: $result->output();
            throw new RuntimeException("Échec de l'extraction OCR Tesseract : {$error}");
        }

        $text = $result->output();

        return $this->cleanExtractedText($text);
    }

    /**
     * Attempt to extract native text embedded in a PDF without OCR.
     */
    protected function extractNativePdfText(string $filePath): string
    {
        try {
            // First check if pdftotext exists on the system
            $pdfToTextCheck = Process::timeout(3)->run(['pdftotext', '-v']);
            if ($pdfToTextCheck->successful()) {
                $result = Process::timeout(15)->run(['pdftotext', '-enc', 'UTF-8', $filePath, '-']);
                if ($result->successful() && ! empty(trim($result->output()))) {
                    return $result->output();
                }
            }

            // Fallback native PHP stream inspection for plain text PDF objects
            $content = file_get_contents($filePath);
            if ($content === false) {
                return '';
            }

            // Simple text extraction from uncompressed stream blocks in PDF
            $extracted = '';
            if (preg_match_all('/\(([^\)]+)\)\s*Tj/i', $content, $matches)) {
                $extracted .= implode(' ', $matches[1])."\n";
            }
            if (preg_match_all('/\[([^\]]+)\]\s*TJ/i', $content, $matches)) {
                foreach ($matches[1] as $tj) {
                    if (preg_match_all('/\(([^\)]+)\)/i', $tj, $parts)) {
                        $extracted .= implode('', $parts[1]).' ';
                    }
                }
            }

            return $extracted;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Clean and normalize extracted text (remove excessive null bytes or control characters).
     */
    protected function cleanExtractedText(string $text): string
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
