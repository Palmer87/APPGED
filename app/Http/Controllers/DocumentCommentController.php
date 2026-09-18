<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplyDocumentCommentRequest;
use App\Http\Requests\StoreDocumentCommentRequest;
use App\Http\Requests\UpdateDocumentCommentRequest;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentVersion;
use App\Services\DocumentCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentCommentController extends Controller
{
    public function __construct(
        protected DocumentCommentService $commentService
    ) {}

    /**
     * List paginated root comments with replies for a document.
     */
    public function index(Request $request, Document $document): JsonResponse
    {
        $versionId = $request->filled('version_id') ? (int) $request->input('version_id') : null;
        $perPage = (int) $request->input('per_page', 20);

        $comments = $this->commentService->getDocumentComments($request->user(), $document, $versionId, $perPage);

        return response()->json($comments);
    }

    /**
     * Store a new root comment on a document.
     */
    public function store(StoreDocumentCommentRequest $request, Document $document): JsonResponse|RedirectResponse
    {
        $versionId = $request->filled('document_version_id') ? (int) $request->input('document_version_id') : null;

        $comment = $this->commentService->create(
            actor: $request->user(),
            document: $document,
            content: $request->validated('content'),
            versionId: $versionId
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Commentaire ajouté avec succès.');
        }

        return response()->json($comment, 201);
    }

    /**
     * Store a comment explicitly on a specific document version.
     */
    public function storeVersionComment(StoreDocumentCommentRequest $request, Document $document, DocumentVersion $version): JsonResponse|RedirectResponse
    {
        if ($version->document_id !== $document->id) {
            throw new HttpException(422, 'Version does not belong to this document.');
        }

        $comment = $this->commentService->create(
            actor: $request->user(),
            document: $document,
            content: $request->validated('content'),
            versionId: $version->id
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Commentaire de version ajouté avec succès.');
        }

        return response()->json($comment, 201);
    }

    /**
     * Reply to an existing root comment.
     */
    public function reply(ReplyDocumentCommentRequest $request, DocumentComment $comment): JsonResponse|RedirectResponse
    {
        $reply = $this->commentService->reply(
            actor: $request->user(),
            parent: $comment,
            content: $request->validated('content')
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Réponse ajoutée avec succès.');
        }

        return response()->json($reply, 201);
    }

    /**
     * Update an existing comment.
     */
    public function update(UpdateDocumentCommentRequest $request, DocumentComment $comment): JsonResponse|RedirectResponse
    {
        $updated = $this->commentService->update(
            actor: $request->user(),
            comment: $comment,
            content: $request->validated('content')
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Commentaire mis à jour.');
        }

        return response()->json($updated);
    }

    /**
     * Delete a comment (soft delete).
     */
    public function destroy(Request $request, DocumentComment $comment): JsonResponse|RedirectResponse
    {
        $this->commentService->delete($request->user(), $comment);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Commentaire supprimé.');
        }

        return response()->json(['message' => 'Comment deleted successfully.']);
    }

    /**
     * Restore a soft-deleted comment.
     */
    public function restore(Request $request, DocumentComment $comment): JsonResponse|RedirectResponse
    {
        $restored = $this->commentService->restore($request->user(), $comment);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Commentaire restauré.');
        }

        return response()->json([
            'message' => 'Comment restored successfully.',
            'comment' => $restored,
        ]);
    }
}
