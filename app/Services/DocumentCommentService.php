<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentCommentService
{
    public function __construct(
        protected ?AuditService $auditService = null,
        protected ?NotificationService $notificationService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
        $this->notificationService = $this->notificationService ?? app(NotificationService::class);
    }

    /**
     * Sanitize and validate raw text content.
     */
    public function sanitizeContent(string $content): string
    {
        $clean = trim(strip_tags($content));

        if ($clean === '') {
            throw new HttpException(422, 'Comment content cannot be empty.');
        }

        if (mb_strlen($clean) > 10000) {
            throw new HttpException(422, 'Comment content cannot exceed 10000 characters.');
        }

        return $clean;
    }

    /**
     * Create a root comment on a document (or specific version).
     */
    public function create(User $actor, Document $document, string $content, ?int $versionId = null): DocumentComment
    {
        if ($actor->organization_id !== $document->organization_id) {
            throw new HttpException(403, 'Cross-tenant access prohibited.');
        }

        if ($document->trashed()) {
            throw new HttpException(422, 'Cannot comment on a trashed document.');
        }

        if ($document->status !== 'active') {
            throw new HttpException(422, 'Cannot comment on an archived document.');
        }

        Gate::forUser($actor)->authorize('create', [DocumentComment::class, $document]);

        if ($versionId !== null) {
            $version = DocumentVersion::find($versionId);
            if (! $version || $version->document_id !== $document->id) {
                throw new HttpException(422, 'Specified document version does not belong to this document.');
            }
        }

        $cleanContent = $this->sanitizeContent($content);

        return DB::transaction(function () use ($actor, $document, $cleanContent, $versionId) {
            $comment = DocumentComment::create([
                'organization_id' => $document->organization_id,
                'document_id' => $document->id,
                'document_version_id' => $versionId,
                'user_id' => $actor->id,
                'parent_id' => null,
                'content' => $cleanContent,
            ]);

            $this->auditService->success(
                action: 'document.comment_created',
                auditable: $comment,
                metadata: [
                    'document_id' => $document->id,
                    'comment_id' => $comment->id,
                    'version_id' => $versionId,
                    'parent_id' => null,
                ],
                user: $actor,
                description: "Comment added on document '{$document->name}'."
            );

            $this->notificationService->notifyDocumentCommented($comment, $actor);

            return $comment->load(['user:id,name,email', 'version:id,version_number,file_name']);
        });
    }

    /**
     * Reply to an existing root comment.
     */
    public function reply(User $actor, DocumentComment $parent, string $content): DocumentComment
    {
        if ($actor->organization_id !== $parent->organization_id) {
            throw new HttpException(403, 'Cross-tenant access prohibited.');
        }

        if ($parent->parent_id !== null) {
            throw new HttpException(422, 'Replies cannot be nested.');
        }

        if ($parent->trashed()) {
            throw new HttpException(422, 'Cannot reply to a deleted comment.');
        }

        $document = $parent->document;
        if (! $document || $document->trashed()) {
            throw new HttpException(422, 'Cannot reply on a trashed document.');
        }

        if ($document->status !== 'active') {
            throw new HttpException(422, 'Cannot reply on an archived document.');
        }

        Gate::forUser($actor)->authorize('reply', $parent);

        $cleanContent = $this->sanitizeContent($content);

        return DB::transaction(function () use ($actor, $parent, $document, $cleanContent) {
            $reply = DocumentComment::create([
                'organization_id' => $parent->organization_id,
                'document_id' => $parent->document_id,
                'document_version_id' => $parent->document_version_id,
                'user_id' => $actor->id,
                'parent_id' => $parent->id,
                'content' => $cleanContent,
            ]);

            $this->auditService->success(
                action: 'document.comment_replied',
                auditable: $reply,
                metadata: [
                    'document_id' => $parent->document_id,
                    'comment_id' => $reply->id,
                    'parent_id' => $parent->id,
                    'version_id' => $parent->document_version_id,
                ],
                user: $actor,
                description: "Reply added to comment #{$parent->id} on document '{$document->name}'."
            );

            $this->notificationService->notifyDocumentCommented($reply, $actor);

            return $reply->load(['user:id,name,email']);
        });
    }

    /**
     * Update an existing comment.
     */
    public function update(User $actor, DocumentComment $comment, string $content): DocumentComment
    {
        if ($actor->organization_id !== $comment->organization_id) {
            throw new HttpException(403, 'Cross-tenant access prohibited.');
        }

        $document = $comment->document;
        if (! $document || $document->trashed() || $document->status !== 'active') {
            throw new HttpException(422, 'Cannot edit comment on an inactive or deleted document.');
        }

        Gate::forUser($actor)->authorize('update', $comment);

        $cleanContent = $this->sanitizeContent($content);

        $comment->update(['content' => $cleanContent]);

        $this->auditService->success(
            action: 'document.comment_updated',
            auditable: $comment,
            metadata: [
                'document_id' => $comment->document_id,
                'comment_id' => $comment->id,
            ],
            user: $actor,
            description: "Comment #{$comment->id} updated."
        );

        return $comment;
    }

    /**
     * Soft delete a comment.
     */
    public function delete(User $actor, DocumentComment $comment): void
    {
        if ($actor->organization_id !== $comment->organization_id) {
            throw new HttpException(403, 'Cross-tenant access prohibited.');
        }

        Gate::forUser($actor)->authorize('delete', $comment);

        $comment->delete();

        $this->auditService->success(
            action: 'document.comment_deleted',
            auditable: $comment,
            metadata: [
                'document_id' => $comment->document_id,
                'comment_id' => $comment->id,
            ],
            user: $actor,
            description: "Comment #{$comment->id} deleted."
        );
    }

    /**
     * Restore a soft-deleted comment.
     */
    public function restore(User $actor, DocumentComment $comment): DocumentComment
    {
        if ($actor->organization_id !== $comment->organization_id) {
            throw new HttpException(403, 'Cross-tenant access prohibited.');
        }

        $document = $comment->document()->withTrashed()->first();
        if (! $document || $document->trashed()) {
            throw new HttpException(422, 'Cannot restore comment of a trashed or deleted document.');
        }

        Gate::forUser($actor)->authorize('restore', $comment);

        $comment->restore();

        $this->auditService->success(
            action: 'document.comment_restored',
            auditable: $comment,
            metadata: [
                'document_id' => $comment->document_id,
                'comment_id' => $comment->id,
            ],
            user: $actor,
            description: "Comment #{$comment->id} restored."
        );

        return $comment;
    }

    /**
     * Get paginated root comments with nested replies for a document.
     */
    public function getDocumentComments(User $actor, Document $document, ?int $versionId = null, int $perPage = 20): LengthAwarePaginator
    {
        if ($actor->organization_id !== $document->organization_id) {
            throw new HttpException(403, 'Cross-tenant access prohibited.');
        }

        if ($document->trashed()) {
            throw new HttpException(404, 'Document is in trash.');
        }

        Gate::forUser($actor)->authorize('view', $document);

        $perPage = max(1, min($perPage, 100));

        $query = DocumentComment::withTrashed()
            ->where('document_id', $document->id)
            ->whereNull('parent_id')
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhereHas('replies', function ($rq) {
                        $rq->whereNull('deleted_at');
                    });
            });

        if ($versionId !== null) {
            $query->where('document_version_id', $versionId);
        }

        $paginator = $query->with([
            'user:id,name,email',
            'version:id,version_number,file_name',
            'replies' => function ($rq) {
                $rq->with('user:id,name,email')->orderBy('created_at', 'asc');
            },
        ])
            ->latest('created_at')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (DocumentComment $comment) {
            if ($comment->trashed()) {
                $comment->content = '[Commentaire supprimé]';
            }

            return $comment;
        });

        return $paginator;
    }
}
