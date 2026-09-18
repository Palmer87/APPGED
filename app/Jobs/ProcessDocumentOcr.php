<?php

namespace App\Jobs;

use App\Enums\OcrStatus;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\DocumentVersion;
use App\Services\OcrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    public function __construct(
        public Document $document,
        public ?DocumentVersion $version = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OcrService $ocrService): void
    {
        // Multi-tenant and soft-delete sanity checks
        if ($this->document->trashed()) {
            Log::info("ProcessDocumentOcr ignoré : le document {$this->document->id} est supprimé.");

            return;
        }

        if ($this->version && $this->version->document_id !== $this->document->id) {
            Log::warning("ProcessDocumentOcr rejeté : la version {$this->version->id} n'appartient pas au document {$this->document->id}.");

            return;
        }

        $ocrService->process($this->document, $this->version);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $message = $exception ? $exception->getMessage() : 'Échec du job ProcessDocumentOcr';

        Log::error("ProcessDocumentOcr a échoué pour le document {$this->document->id} : {$message}");

        $targetVersion = $this->version ?? $this->document->currentVersion;

        DocumentOcr::updateOrCreate(
            [
                'document_id' => $this->document->id,
                'document_version_id' => $targetVersion?->id,
            ],
            [
                'organization_id' => $this->document->organization_id,
                'status' => OcrStatus::Failed,
                'error_message' => $message,
                'processed_at' => now(),
            ]
        );
    }
}
