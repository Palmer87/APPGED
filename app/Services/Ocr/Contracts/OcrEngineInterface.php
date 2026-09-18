<?php

namespace App\Services\Ocr\Contracts;

interface OcrEngineInterface
{
    /**
     * Determine if the OCR engine is available and ready for execution on the host system.
     */
    public function isAvailable(): bool;

    /**
     * Extract text from the given local file path.
     *
     * @param  string  $filePath  Absolute path to the local file
     * @param  string  $extension  Lowercase file extension (e.g. 'pdf', 'jpg', 'png')
     * @param  array<string, mixed>  $options  Additional engine options (languages, timeout, etc.)
     * @return string Extracted plain text
     *
     * @throws \RuntimeException If extraction fails or engine is not available
     */
    public function extractText(string $filePath, string $extension, array $options = []): string;
}
