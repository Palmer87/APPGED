# GEDAPP — Storage Architecture

## Overview

GEDAPP uses Laravel's Filesystem abstraction to manage document storage. This design allows transparent switching between local storage (development/testing) and S3-compatible object storage like Cloudflare R2 (production) without modifying business logic.

```
                 GEDAPP
                    │
                    ▼
            Laravel Filesystem
                    │
             ┌──────┴──────┐
             │             │
          Local          S3/R2
             │             │
             │             ▼
             │       Cloudflare R2
             │             │
             │       djudevstorage
             │
       développement/tests
```

## Configured Disks

| Disk      | Driver  | Usage                                  |
|-----------|---------|----------------------------------------|
| `local`   | `local` | Default Laravel local disk             |
| `private` | `local` | Alias for private document storage     |
| `s3`      | `s3`    | Generic AWS S3 disk                    |
| `r2`      | `s3`    | Cloudflare R2 (S3-compatible)          |
| `public`  | `local` | Public storage (not used for documents)|

All document disks are configured with `'visibility' => 'private'`.

## Document Storage Disk Selection

The disk used for new document uploads is determined by the following priority:

1. **Explicit `storage_disk`** parameter passed to `DocumentService::upload()`
2. **`DOCUMENTS_DISK`** environment variable
3. **`FILESYSTEM_DISK`** environment variable
4. **`'private'`** (hardcoded fallback)

```php
// config/filesystems.php
'documents_disk' => env('DOCUMENTS_DISK', env('FILESYSTEM_DISK', 'private')),
```

Once a document is created, its `storage_disk` is persisted in the database. All subsequent operations (download, preview, version upload, deletion) use the stored disk value, not the current config. This ensures documents created on one disk remain accessible even after switching the default.

## Storage Path Structure

Documents and versions follow a strict multi-tenant path structure:

```
organizations/{organization_id}/documents/{document_id}/versions/{version_number}/{uuid}.{ext}
```

Example:
```
organizations/1/documents/42/versions/1/550e8400-e29b-41d4-a716-446655440000.pdf
organizations/1/documents/42/versions/2/6ba7b810-9dad-11d1-80b4-00c04fd430c8.pdf
```

This structure:
- Isolates tenants at the filesystem level
- Keeps all versions of a document grouped together
- Uses UUIDs to prevent filename collisions
- Makes cleanup (force delete) straightforward via `deleteDirectory()`

## Environment Variables

### For Cloudflare R2 (Production)

Add these variables to your `.env` file:

```env
# Switch document storage to R2
DOCUMENTS_DISK=r2

# R2-specific credentials (preferred)
R2_ACCESS_KEY_ID=your_r2_access_key_id
R2_SECRET_ACCESS_KEY=your_r2_secret_access_key
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_BUCKET=djudevstorage
R2_DEFAULT_REGION=auto

# Or use AWS_* variables (R2 falls back to these)
# AWS_ACCESS_KEY_ID=your_access_key
# AWS_SECRET_ACCESS_KEY=your_secret_key
# AWS_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
# AWS_BUCKET=djudevstorage
# AWS_DEFAULT_REGION=auto
```

### For AWS S3

```env
DOCUMENTS_DISK=s3

AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=your-bucket-name
```

### For Local Development

```env
# No additional config needed — defaults to 'private' local disk
# DOCUMENTS_DISK=private
```

> **IMPORTANT**: Never commit credentials to version control. Never put secrets in PHP, JS, or config files.

## Document Operations

### Upload

```
DocumentService::upload() → Storage::disk($configured_disk)->put()
```

Files are stored inside a DB transaction. If the transaction fails, the orphan file is automatically cleaned up.

### New Version

```
DocumentService::uploadNewVersion() → Storage::disk($document->storage_disk)->put()
```

New versions inherit the disk from the parent document.

### Version Restore

```
DocumentService::restoreVersion() → creates a new version (V_n+1) with a physical copy
```

The original version file remains intact. Restoration never modifies existing version files.

### Download

```
DocumentService::download() / downloadVersion() → Storage::disk($version->storage_disk)->download()
```

The server:
1. Verifies authentication
2. Checks tenant isolation (`organization_id`)
3. Authorizes via `DocumentPolicy` + `AccessControlService`
4. Streams the file from the configured disk

The browser never accesses the bucket directly.

### Preview

```
PreviewService::preview() → Storage::disk($disk)->readStream()
```

Supported formats: PDF, JPEG, PNG, WEBP.

The file is streamed with `Content-Disposition: inline` and `X-Content-Type-Options: nosniff`.

### Trash (Soft Delete)

Physical files are **preserved** when a document is moved to trash. This allows restoration without data loss.

### Force Delete (Permanent)

```
DocumentLifecycleService::forceDelete()
```

1. Deletes all version files from their respective disk
2. Deletes the document's main file
3. Attempts to remove the document directory
4. Logs audit trail
5. Performs database `forceDelete()` (cascades to versions, shares, etc.)

Each file is deleted from its actual `storage_disk`, not a global config — this correctly handles documents that may have been created on different disks.

## Signed URLs

`DocumentService::getTemporaryUrl()` generates short-lived (15 min default) signed URLs for S3/R2 disks. This is useful for direct browser access to PDFs or images without streaming through the server.

**Security guarantees:**
- Multi-tenant isolation is verified before URL generation
- `DocumentPolicy::download` authorization is enforced
- Falls back to the authenticated download route if the disk doesn't support temporary URLs (e.g., local disk)

## Multi-Tenant Security

- Storage paths are always derived from `organization_id` — never from user input
- All download, preview, and version operations verify `$user->organization_id === $document->organization_id`
- Policies and `AccessControlService` enforce fine-grained permissions
- Even if a user knows the R2 storage path, they cannot access files without going through the authenticated Laravel routes
- The R2 bucket is private — no public access

## Testing

### Automated Tests

Tests use `Storage::fake()` and never connect to the real R2/S3 bucket:

```bash
php artisan test --filter=StorageR2Test
```

The test suite covers:
- Disk configuration validation
- Upload to configured disk (r2, private, s3)
- Version upload on same disk
- Version restore with physical copy
- Preview streaming from disk
- Download from disk
- Trash preserves files
- Force delete purges files
- Orphan file cleanup on failure
- Cross-tenant access forbidden
- API compatibility
- Mixed disk scenarios (old docs on local, new on R2)

### Manual R2 Testing

To test with your real Cloudflare R2 bucket:

1. Configure `.env` with your R2 credentials (see above)
2. Start the application: `php artisan serve`
3. Upload a document via the web UI or API
4. Verify the file appears in your R2 bucket (Cloudflare dashboard)
5. Download the document — verify content matches
6. Preview a PDF — verify inline display
7. Upload a new version — verify both files exist
8. Delete the document (trash) — verify files still in R2
9. Restore from trash — verify download still works
10. Permanently delete — verify files removed from R2

### Verify Connection

```bash
php artisan tinker --execute "
    \$disk = Storage::disk('r2');
    \$disk->put('test-connection.txt', 'GEDAPP R2 test');
    echo \$disk->exists('test-connection.txt') ? 'OK' : 'FAIL';
    \$disk->delete('test-connection.txt');
"
```

## Dependencies

| Package                        | Version | Purpose                     |
|--------------------------------|---------|-----------------------------|
| `league/flysystem-aws-s3-v3`  | ^3.35   | S3/R2 Flysystem adapter    |
| `aws/aws-sdk-php`             | ^3.395  | AWS SDK (auto-installed)    |

## Compatibility

This storage layer is designed to be transparent to:
- **Module 19 (OCR)**: Will read files via `Storage::disk($doc->storage_disk)->get()`
- **Module 20 (React Native)**: Will call existing API endpoints — no direct R2 access
- **All existing modules**: No changes needed — they use the Storage facade
