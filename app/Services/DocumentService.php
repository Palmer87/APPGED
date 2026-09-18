<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    public function __construct(
        protected ?AuditService $auditService = null,
        protected ?NotificationService $notificationService = null,
        protected ?OcrService $ocrService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->notificationService = $this->notificationService ?? app(NotificationService::class);
        $this->ocrService = $this->ocrService ?? app(OcrService::class);
    }

    /**
     * Upload a new document and create its first version (V1).
     *
     * @param  array{organization_id: int, uploaded_by: int, folder_id?: int|null, name?: string, description?: string|null, storage_disk?: string}  $data
     */
    public function upload(array $data, UploadedFile $file): Document
    {
        // Validate tenant ownership of folder (if provided) and resolve document type
        $documentTypeId = null;
        if (! empty($data['folder_id'])) {
            $folder = Folder::findOrFail($data['folder_id']);
            if ($folder->organization_id !== $data['organization_id']) {
                abort(403, 'Folder does not belong to your organization');
            }
            $documentTypeId = $folder->getDocumentType()?->id;
        }

        $this->validateFile($file);

        $orgId = $data['organization_id'];
        $disk = $data['storage_disk'] ?? config('filesystems.documents_disk', config('filesystems.default', 'private'));
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // We store to a temporary path first, then move into the version path after DB insert
        $uuid = (string) Str::uuid();
        $versionNumber = 1;

        // Prepare document attributes (will be updated with version path after insert)
        $docAttributes = [
            'organization_id' => $orgId,
            'folder_id' => $data['folder_id'] ?? null,
            'document_type_id' => $data['document_type_id'] ?? $documentTypeId,
            'uploaded_by' => $data['uploaded_by'],
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'description' => $data['description'] ?? null,
            'file_name' => '',
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'storage_disk' => $disk,
            'storage_path' => '',
            'status' => 'active',
        ];

        // Create document first in transaction to get its ID, then store file
        $storedPath = null;

        try {
            $document = DB::transaction(function () use ($docAttributes, $disk, $orgId, $uuid, $extension, $versionNumber, $file, $mimeType, $size, &$storedPath) {
                $document = Document::create($docAttributes);

                // Build version storage path: organizations/{orgId}/documents/{docId}/versions/{versionNumber}/{uuid}.{ext}
                $fileName = $uuid.'.'.$extension;
                $storagePath = "organizations/{$orgId}/documents/{$document->id}/versions/{$versionNumber}/{$fileName}";

                // Store the file
                Storage::disk($disk)->put($storagePath, file_get_contents($file->getRealPath()));
                $storedPath = $storagePath;

                // Create DocumentVersion
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'uploaded_by' => $document->uploaded_by,
                    'version_number' => $versionNumber,
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $size,
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath,
                    'comment' => 'Initial version',
                ]);

                // Update document with version file info
                $document->update([
                    'file_name' => $fileName,
                    'storage_path' => $storagePath,
                ]);

                return $document->fresh();
            }, 5);

            $this->auditService->success(
                action: 'document.created',
                auditable: $document,
                newValues: [
                    'name' => $document->name,
                    'extension' => $document->extension,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                    'folder_id' => $document->folder_id,
                ],
                description: "Document '{$document->name}' created with initial version."
            );

            // Automatically dispatch background OCR processing
            $this->ocrService->dispatchOcr($document, $document->currentVersion);

            return $document;
        } catch (\Throwable $e) {
            // Cleanup orphan file if transaction failed
            if ($storedPath) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $e;
        }
    }

    /**
     * Upload a new version of an existing document.
     */
    public function uploadNewVersion(Document $document, UploadedFile $file, ?string $comment = null): DocumentVersion
    {
        $user = auth()->user();

        // Multi-tenant check: user must belong to same organization as document
        // Super-admin bypasses organization isolation
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        // Document must not be soft-deleted
        if ($document->trashed()) {
            abort(403, 'Cannot upload a version to a deleted document');
        }

        // Permission check
        Gate::authorize('update', $document);

        $this->validateFile($file);

        $disk = $document->storage_disk;
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $uuid = (string) Str::uuid();
        $fileName = $uuid.'.'.$extension;
        $storedPath = null;

        try {
            $version = DB::transaction(function () use ($document, $disk, $extension, $mimeType, $size, $file, $fileName, $comment, &$storedPath) {
                // Lock the document's versions to prevent concurrent version_number collision
                $nextVersionNumber = DB::table('document_versions')
                    ->where('document_id', $document->id)
                    ->lockForUpdate()
                    ->max('version_number');
                $nextVersionNumber = ($nextVersionNumber ?? 0) + 1;

                // Build storage path
                $storagePath = "organizations/{$document->organization_id}/documents/{$document->id}/versions/{$nextVersionNumber}/{$fileName}";

                // Store the file physically
                Storage::disk($disk)->put($storagePath, file_get_contents($file->getRealPath()));
                $storedPath = $storagePath;

                // Create the version record
                $version = DocumentVersion::create([
                    'document_id' => $document->id,
                    'uploaded_by' => auth()->id(),
                    'version_number' => $nextVersionNumber,
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $size,
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath,
                    'comment' => $comment,
                ]);

                // Update the document to reflect the latest version
                $document->update([
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $size,
                    'storage_path' => $storagePath,
                ]);

                return $version;
            }, 5);

            $this->auditService->success(
                action: 'document.version_created',
                auditable: $document,
                target: $version,
                newValues: [
                    'version_number' => $version->version_number,
                    'file_name' => $version->file_name,
                    'size' => $version->size,
                    'comment' => $version->comment,
                ],
                description: "New version {$version->version_number} uploaded for document '{$document->name}'."
            );

            $this->notificationService->notifyDocumentVersionCreated($document, $user, $version);

            // Automatically dispatch background OCR processing for the new version
            $this->ocrService->dispatchOcr($document, $version);

            return $version;
        } catch (\Throwable $e) {
            // Cleanup orphan file if transaction failed
            if ($storedPath) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $e;
        }
    }

    /**
     * Restore an older version by creating a new version with the same file content.
     *
     * Example: V1=A, V2=B, V3=C → restoreVersion(V1) → V4=A (new physical copy)
     */
    public function restoreVersion(Document $document, DocumentVersion $version): DocumentVersion
    {
        $user = auth()->user();

        // Multi-tenant check
        // Super-admin bypasses organization isolation
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        // Version must belong to the document
        if ($version->document_id !== $document->id) {
            abort(403, 'This version does not belong to the specified document');
        }

        // Document must not be soft-deleted
        if ($document->trashed()) {
            abort(403, 'Cannot restore a version of a deleted document');
        }

        // Permission check
        Gate::authorize('update', $document);

        $disk = $document->storage_disk;
        $uuid = (string) Str::uuid();
        $fileName = $uuid.'.'.$version->extension;
        $storedPath = null;

        try {
            $newVersion = DB::transaction(function () use ($document, $version, $disk, $fileName, &$storedPath) {
                // Lock to determine next version number
                $nextVersionNumber = DB::table('document_versions')
                    ->where('document_id', $document->id)
                    ->lockForUpdate()
                    ->max('version_number');
                $nextVersionNumber = ($nextVersionNumber ?? 0) + 1;

                // Build new storage path
                $storagePath = "organizations/{$document->organization_id}/documents/{$document->id}/versions/{$nextVersionNumber}/{$fileName}";

                // Copy the original version's file to the new path
                $originalContent = Storage::disk($version->storage_disk)->get($version->storage_path);
                Storage::disk($disk)->put($storagePath, $originalContent);
                $storedPath = $storagePath;

                // Create the new version record
                $newVersion = DocumentVersion::create([
                    'document_id' => $document->id,
                    'uploaded_by' => auth()->id(),
                    'version_number' => $nextVersionNumber,
                    'file_name' => $fileName,
                    'mime_type' => $version->mime_type,
                    'extension' => $version->extension,
                    'size' => $version->size,
                    'storage_disk' => $disk,
                    'storage_path' => $storagePath,
                    'comment' => "Restored from version {$version->version_number}",
                ]);

                // Update document to reflect the restored version
                $document->update([
                    'file_name' => $fileName,
                    'mime_type' => $version->mime_type,
                    'extension' => $version->extension,
                    'size' => $version->size,
                    'storage_path' => $storagePath,
                ]);

                return $newVersion;
            }, 5);

            $this->auditService->success(
                action: 'document.version_restored',
                auditable: $document,
                target: $newVersion,
                metadata: [
                    'source_version' => $version->version_number,
                    'restored_as_version' => $newVersion->version_number,
                ],
                description: "Document '{$document->name}' restored to content of version {$version->version_number} (new version {$newVersion->version_number})."
            );

            // Automatically dispatch background OCR processing for the restored version
            $this->ocrService->dispatchOcr($document, $newVersion);

            return $newVersion;
        } catch (\Throwable $e) {
            if ($storedPath) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $e;
        }
    }

    /**
     * Download a specific version of a document.
     */
    public function downloadVersion(Document $document, DocumentVersion $version): StreamedResponse
    {
        $user = auth()->user();

        // Multi-tenant check
        // Super-admin bypasses organization isolation
        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        // Version must belong to the document
        if ($version->document_id !== $document->id) {
            abort(403, 'This version does not belong to the specified document');
        }

        // Permission check
        Gate::authorize('download', $document);

        // Verify file exists
        if (! Storage::disk($version->storage_disk)->exists($version->storage_path)) {
            abort(404, 'Version file not found on storage');
        }

        $this->auditService->success(
            action: 'document.downloaded',
            auditable: $document,
            target: $version,
            user: $user,
            description: "Document '{$document->name}' (V{$version->version_number}) downloaded."
        );

        return Storage::disk($version->storage_disk)->download(
            $version->storage_path,
            $version->file_name
        );
    }

    /**
     * Download the latest version of a document.
     */
    public function download(Document $document): StreamedResponse
    {
        $version = $document->currentVersion;
        if (! $version) {
            abort(404, 'Version file not found for this document');
        }

        return $this->downloadVersion($document, $version);
    }

    /**
     * Generate a secure, short-lived signed temporary URL if the disk supports it.
     * Always validates multi-tenant boundaries and permissions first.
     */
    public function getTemporaryUrl(Document $document, ?DocumentVersion $version = null, ?\DateTimeInterface $expiration = null): string
    {
        $user = auth()->user();

        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        if ($document->trashed()) {
            abort(403, 'Cannot generate download URL for a deleted document');
        }

        Gate::authorize('download', $document);

        $targetVersion = $version ?? $document->currentVersion;
        if (! $targetVersion) {
            abort(404, 'Version file not found');
        }

        $disk = $targetVersion->storage_disk;
        $path = $targetVersion->storage_path;
        $expires = $expiration ?? now()->addMinutes(15);

        try {
            return Storage::disk($disk)->temporaryUrl($path, $expires);
        } catch (\Throwable) {
            // Fallback to internal authenticated download route if driver doesn't support temporaryUrl
            return route('documents.download', $document);
        }
    }

    /**
     * Soft-delete a document.
     */
    public function delete(Document $document): void
    {
        $document->delete();
        // Physical file removal is deferred for later phases.
    }

    /**
     * Restore a soft-deleted document.
     */
    public function restore(Document $document): void
    {
        $document->restore();
    }

    /**
     * Move a document to another folder (or to root if null) within the same organization.
     */
    public function move(Document $document, ?Folder $targetFolder): void
    {
        if ($targetFolder && $document->organization_id !== $targetFolder->organization_id) {
            abort(403, 'Target folder belongs to a different organization');
        }

        $oldFolderId = $document->folder_id;
        $oldDocTypeId = $document->document_type_id;

        $newDocTypeId = $targetFolder ? $targetFolder->getDocumentType()?->id : null;

        $document->folder_id = $targetFolder?->id;
        $document->document_type_id = $newDocTypeId;
        $document->save();

        $this->auditService->success(
            action: 'document.moved',
            auditable: $document,
            oldValues: [
                'folder_id' => $oldFolderId,
                'document_type_id' => $oldDocTypeId,
            ],
            newValues: [
                'folder_id' => $document->folder_id,
                'document_type_id' => $document->document_type_id,
            ],
            description: "Document '{$document->name}' moved."
        );
    }

    /**
     * Validate file against config/documents.php constraints.
     */
    private function validateFile(UploadedFile $file): void
    {
        $allowedExtensions = config('documents.allowed_extensions', []);
        $maxSizeKb = config('documents.max_file_size_kb', 10240);

        $extension = strtolower($file->getClientOriginalExtension());

        if (! empty($allowedExtensions) && ! in_array($extension, $allowedExtensions, true)) {
            abort(422, "File extension '{$extension}' is not allowed");
        }

        // UploadedFile::getSize() returns bytes
        if ($file->getSize() > $maxSizeKb * 1024) {
            abort(422, 'File exceeds the maximum allowed size');
        }
    }

    /**
     * Set the category of a document (replacing any existing one).
     */
    public function setCategory(Document $document, ?Category $category): void
    {
        if ($document->trashed()) {
            abort(404, 'Document is deleted');
        }

        if ($category !== null) {
            if ($category->trashed() || $category->organization_id !== $document->organization_id) {
                abort(403, 'Category belongs to a different organization or is deleted');
            }
        }

        DB::transaction(function () use ($document, $category) {
            if ($category === null) {
                $document->categories()->detach();
            } else {
                $document->categories()->sync([$category->id]);
            }
        });

        $this->auditService->success(
            action: 'document.category_updated',
            auditable: $document,
            target: $category,
            newValues: [
                'category_id' => $category?->id,
                'category_name' => $category?->name,
            ],
            description: $category ? "Document categorized as '{$category->name}'." : 'Document category removed.'
        );
    }

    /**
     * Sync the tags of a document atomically.
     * All tags must belong to the document's organization.
     */
    public function syncTags(Document $document, array $tagIds): void
    {
        if ($document->trashed()) {
            abort(404, 'Document is deleted');
        }

        $uniqueTagIds = array_values(array_unique($tagIds));

        if (empty($uniqueTagIds)) {
            $document->tags()->detach();

            $this->auditService->success(
                action: 'document.tags_updated',
                auditable: $document,
                newValues: ['tag_ids' => []],
                description: 'All document tags removed.'
            );

            return;
        }

        DB::transaction(function () use ($document, $uniqueTagIds) {
            $validTagsCount = Tag::whereIn('id', $uniqueTagIds)
                ->where('organization_id', $document->organization_id)
                ->count();

            if ($validTagsCount !== count($uniqueTagIds)) {
                abort(403, 'One or more tags belong to a different organization or do not exist');
            }

            $document->tags()->sync($uniqueTagIds);
        });

        $this->auditService->success(
            action: 'document.tags_updated',
            auditable: $document,
            newValues: ['tag_ids' => $uniqueTagIds],
            description: count($uniqueTagIds).' tag(s) assigned to document.'
        );
    }
}
