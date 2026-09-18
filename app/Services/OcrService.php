<?php

namespace App\Services;

use App\Enums\OcrStatus;
use App\Jobs\ProcessDocumentOcr;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Ocr\Contracts\OcrEngineInterface;
use App\Services\Ocr\Engines\TesseractEngine;
use App\Services\Ocr\Engines\TestingEngine;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OcrService
{
    protected ?OcrEngineInterface $engine = null;

    public function __construct(
        protected ?AuditService $auditService = null,
        protected ?NotificationService $notificationService = null,
        ?OcrEngineInterface $engine = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->notificationService = $this->notificationService ?? app(NotificationService::class);
        $this->engine = $engine;
    }

    /**
     * Get or resolve the OCR engine.
     */
    public function getEngine(): OcrEngineInterface
    {
        if ($this->engine !== null) {
            return $this->engine;
        }

        $driver = config('ocr.driver', 'tesseract');

        if ($driver === 'testing' || app()->runningUnitTests()) {
            $this->engine = new TestingEngine;
        } else {
            $this->engine = new TesseractEngine;
        }

        return $this->engine;
    }

    /**
     * Override or mock the OCR engine (useful in tests).
     */
    public function setEngine(OcrEngineInterface $engine): static
    {
        $this->engine = $engine;

        return $this;
    }

    /**
     * Determine whether the given file extension is supported for OCR.
     */
    public function isFormatSupported(string $extension): bool
    {
        $ext = strtolower(ltrim(trim($extension), '.'));
        $supported = config('ocr.supported_extensions', ['pdf', 'jpg', 'jpeg', 'png', 'webp']);

        return in_array($ext, $supported, true);
    }

    /**
     * Determine whether the given file extension is explicitly skipped.
     */
    public function isFormatSkipped(string $extension): bool
    {
        $ext = strtolower(ltrim(trim($extension), '.'));
        $skipped = config('ocr.skipped_extensions', ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip']);

        return in_array($ext, $skipped, true);
    }

    /**
     * Dispatch an asynchronous OCR job for a document (and optional version).
     */
    public function dispatchOcr(Document $document, ?DocumentVersion $version = null, ?User $actor = null): DocumentOcr
    {
        // Multi-tenant sanity check
        if ($version && $version->document_id !== $document->id) {
            abort(422, 'La version spécifiée n\'appartient pas à ce document.');
        }

        // Initialize or update OCR tracking record to Pending
        $ocr = DocumentOcr::updateOrCreate(
            [
                'document_id' => $document->id,
                'document_version_id' => $version?->id,
            ],
            [
                'organization_id' => $document->organization_id,
                'status' => OcrStatus::Pending,
                'error_message' => null,
            ]
        );

        // Dispatch background job
        ProcessDocumentOcr::dispatch($document, $version);

        return $ocr;
    }

    /**
     * Execute OCR extraction synchronously on a document and version.
     */
    public function process(Document $document, ?DocumentVersion $version = null): DocumentOcr
    {
        // Target version or latest version
        $targetVersion = $version ?? $document->currentVersion;
        $extension = strtolower($targetVersion ? $targetVersion->extension : $document->extension);
        $disk = $targetVersion ? $targetVersion->storage_disk : $document->storage_disk;
        $storagePath = $targetVersion ? $targetVersion->storage_path : $document->storage_path;
        $size = $targetVersion ? $targetVersion->size : $document->size;

        $ocr = DocumentOcr::firstOrNew([
            'document_id' => $document->id,
            'document_version_id' => $targetVersion?->id,
        ]);
        $ocr->organization_id = $document->organization_id;

        // Check if OCR is enabled
        if (! config('ocr.enabled', true)) {
            $ocr->status = OcrStatus::Skipped;
            $ocr->error_message = 'Traitement OCR désactivé globalement.';
            $ocr->processed_at = now();
            $ocr->save();

            return $ocr;
        }

        // Check if format is unsupported or skipped
        if ($this->isFormatSkipped($extension) || ! $this->isFormatSupported($extension)) {
            $ocr->status = OcrStatus::Skipped;
            $ocr->error_message = "Format de fichier '.{$extension}' non pris en charge pour l'OCR en V1.";
            $ocr->processed_at = now();
            $ocr->save();

            return $ocr;
        }

        // Check file size limits
        $maxBytes = config('ocr.max_file_size_kb', 25600) * 1024;
        if ($size > $maxBytes) {
            $ocr->status = OcrStatus::Skipped;
            $ocr->error_message = 'Fichier trop volumineux pour le traitement OCR.';
            $ocr->processed_at = now();
            $ocr->save();

            return $ocr;
        }

        // Set status to processing
        $ocr->status = OcrStatus::Processing;
        $ocr->error_message = null;
        $ocr->save();

        // Audit OCR started
        $this->auditService->log(
            action: 'document.ocr_started',
            result: 'success',
            auditable: $document,
            target: $targetVersion,
            metadata: [
                'ocr_id' => $ocr->id,
                'extension' => $extension,
                'version' => $targetVersion?->version_number ?? 1,
            ],
            description: "Traitement OCR démarré pour le document '{$document->name}'."
        );

        $tempFilePath = null;
        $startTime = microtime(true);

        try {
            // Verify file exists on private Storage (local or Cloudflare R2)
            if (! Storage::disk($disk)->exists($storagePath)) {
                throw new RuntimeException("Fichier source introuvable sur le disque de stockage '{$disk}' : {$storagePath}");
            }

            // Stream / copy to temporary file for OCR processing
            $tempDir = storage_path('app/temp/ocr');
            if (! File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            $tempFileName = 'ocr_'.Str::uuid().'.'.$extension;
            $tempFilePath = $tempDir.DIRECTORY_SEPARATOR.$tempFileName;

            $fileContents = Storage::disk($disk)->get($storagePath);
            File::put($tempFilePath, $fileContents);

            // Execute engine
            $engine = $this->getEngine();
            $extractedText = $engine->extractText($tempFilePath, $extension, [
                'languages' => config('ocr.languages', ['fra', 'eng']),
                'timeout' => config('ocr.timeout', 120),
            ]);

            $executionTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $wordCount = str_word_count($extractedText);

            $ocr->status = OcrStatus::Completed;
            $ocr->extracted_text = $extractedText;
            $ocr->word_count = $wordCount;
            $ocr->confidence = 95.0;
            $ocr->execution_time_ms = $executionTimeMs;
            $ocr->processed_at = now();
            $ocr->error_message = null;
            $ocr->save();

            // Audit OCR completed
            $this->auditService->success(
                action: 'document.ocr_completed',
                auditable: $document,
                target: $targetVersion,
                newValues: [
                    'ocr_id' => $ocr->id,
                    'word_count' => $wordCount,
                    'execution_time_ms' => $executionTimeMs,
                ],
                description: "Traitement OCR réussi pour '{$document->name}' ({$wordCount} mots extraits)."
            );

            // Send notification
            $this->notificationService->notifyDocumentOcrCompleted($document, $ocr);

            return $ocr;
        } catch (\Throwable $e) {
            $ocr->status = OcrStatus::Failed;
            $ocr->error_message = $e->getMessage();
            $ocr->processed_at = now();
            $ocr->save();

            // Audit OCR failed
            $this->auditService->failure(
                action: 'document.ocr_failed',
                reason: $e->getMessage(),
                auditable: $document,
                target: $targetVersion,
                metadata: [
                    'error' => $e->getMessage(),
                ],
                description: "Échec du traitement OCR pour '{$document->name}' : {$e->getMessage()}"
            );

            // Send notification
            $this->notificationService->notifyDocumentOcrFailed($document, $ocr);

            return $ocr;
        } finally {
            // Guarantee cleanup of temporary local file
            if ($tempFilePath && File::exists($tempFilePath)) {
                File::delete($tempFilePath);
            }
        }
    }
}
