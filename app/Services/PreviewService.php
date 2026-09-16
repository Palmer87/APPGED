<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreviewService
{
    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Supported MIME types for in-browser preview V1.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Supported file extensions for in-browser preview V1.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_EXTENSIONS = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    /**
     * Preview a document or a specific version of a document.
     */
    public function preview(Document $document, ?DocumentVersion $version = null, ?User $user = null): StreamedResponse
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        // 1. Strict multi-tenant isolation (super-admin bypasses organization check)
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        // 2. Soft delete check
        if ($document->trashed()) {
            abort(404, 'Document not found');
        }

        // 3. Authorization check via existing DocumentPolicy & AccessControlService
        // Requires 'documents.view' (does NOT require 'documents.download')
        Gate::forUser($user)->authorize('view', $document);

        // 4. Version consistency check
        if ($version !== null) {
            if ($version->document_id !== $document->id) {
                abort(403, 'This version does not belong to the specified document');
            }

            $disk = $version->storage_disk;
            $path = $version->storage_path;
            $mimeType = $version->mime_type;
            $fileName = $version->file_name;
        } else {
            $disk = $document->storage_disk;
            $path = $document->storage_path;
            $mimeType = $document->mime_type;
            $fileName = $document->file_name;
        }

        // 5. Verify format is supported for V1 preview
        if (! in_array(strtolower($mimeType), self::SUPPORTED_MIME_TYPES, true)) {
            abort(415, 'File format not supported for preview');
        }

        // 6. Verify physical file exists on private storage
        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Document file not found on storage');
        }

        // 7. Stream file with strict inline disposition and nosniff header
        $this->auditService->success(
            action: 'document.previewed',
            auditable: $document,
            target: $version,
            metadata: [
                'version_number' => $version?->version_number ?? 1,
                'mime_type' => $mimeType,
                'file_name' => $fileName,
            ],
            user: $user,
            description: "Document '{$document->name}' previewed."
        );

        return response()->stream(function () use ($disk, $path) {
            $stream = Storage::disk($disk)->readStream($path);
            if ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.addcslashes($fileName, '"').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
