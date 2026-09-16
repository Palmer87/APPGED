<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentShareResource;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Group;
use App\Models\User;
use App\Services\DocumentShareService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentShareController extends Controller
{
    public function __construct(
        protected DocumentShareService $shareService
    ) {}

    /**
     * List all active and past shares for a document.
     */
    public function index(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $shares = $document->shares()
            ->with(['user', 'group', 'sharedBy'])
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => DocumentShareResource::collection($shares),
        ]);
    }

    /**
     * Share document with a user.
     */
    public function storeUserShare(Request $request, Document $document): JsonResponse
    {
        $actor = $request->user();
        Gate::authorize('share', $document);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['nullable', 'string', 'in:view,download,edit,admin'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $permission = $validated['permission'] ?? 'view';
        $expiresAt = ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;

        $share = $this->shareService->shareWithUser($actor, $document, $targetUser, $permission, $expiresAt);

        return response()->json([
            'data' => new DocumentShareResource($share->load(['user', 'sharedBy'])),
            'message' => 'Document shared with user successfully.',
        ], 201);
    }

    /**
     * Share document with a group.
     */
    public function storeGroupShare(Request $request, Document $document): JsonResponse
    {
        $actor = $request->user();
        Gate::authorize('share', $document);

        $validated = $request->validate([
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'permission' => ['nullable', 'string', 'in:view,download,edit,admin'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $group = Group::findOrFail($validated['group_id']);
        $permission = $validated['permission'] ?? 'view';
        $expiresAt = ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;

        $share = $this->shareService->shareWithGroup($actor, $document, $group, $permission, $expiresAt);

        return response()->json([
            'data' => new DocumentShareResource($share->load(['group', 'sharedBy'])),
            'message' => 'Document shared with group successfully.',
        ], 201);
    }

    /**
     * Revoke a document share.
     */
    public function destroy(Document $document, DocumentShare $share): JsonResponse
    {
        $actor = request()->user();
        Gate::authorize('share', $document);

        if ($share->document_id !== $document->id) {
            abort(404, 'Share does not belong to this document.');
        }

        $this->shareService->revoke($actor, $share);

        return response()->json([
            'message' => 'Share revoked successfully.',
        ]);
    }
}
