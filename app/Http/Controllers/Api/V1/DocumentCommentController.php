<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentCommentResource;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Services\DocumentCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentCommentController extends Controller
{
    public function __construct(
        protected DocumentCommentService $commentService
    ) {}

    /**
     * List comments on a document.
     */
    public function index(Request $request, Document $document): JsonResponse
    {
        $actor = $request->user();
        $versionId = $request->filled('version_id') ? (int) $request->input('version_id') : null;
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $comments = $this->commentService->getDocumentComments($actor, $document, $versionId, $perPage);

        return response()->json([
            'data' => DocumentCommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    /**
     * Post a comment on a document.
     */
    public function store(Request $request, Document $document): JsonResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'version_id' => ['nullable', 'integer', 'exists:document_versions,id'],
        ]);

        $comment = $this->commentService->create(
            $actor,
            $document,
            $validated['content'],
            $validated['version_id'] ?? null
        );

        return response()->json([
            'data' => new DocumentCommentResource($comment->load('user')),
            'message' => 'Comment posted successfully.',
        ], 201);
    }

    /**
     * Reply to an existing comment.
     */
    public function reply(Request $request, DocumentComment $comment): JsonResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $reply = $this->commentService->reply(
            $actor,
            $comment,
            $validated['content']
        );

        return response()->json([
            'data' => new DocumentCommentResource($reply->load('user')),
            'message' => 'Reply posted successfully.',
        ], 201);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, DocumentComment $comment): JsonResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $updated = $this->commentService->update(
            $actor,
            $comment,
            $validated['content']
        );

        return response()->json([
            'data' => new DocumentCommentResource($updated->load('user')),
            'message' => 'Comment updated successfully.',
        ]);
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, DocumentComment $comment): JsonResponse
    {
        $actor = $request->user();

        $this->commentService->delete($actor, $comment);

        return response()->json([
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
