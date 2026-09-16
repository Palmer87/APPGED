<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupDocumentShareRequest;
use App\Http\Requests\StoreUserDocumentShareRequest;
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
     * List all active shares for a document.
     */
    public function index(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('super-admin') && $user->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        Gate::forUser($user)->authorize('share', $document);

        $shares = $this->shareService->getActiveShares($document);

        return response()->json($shares);
    }

    /**
     * Share a document with a user.
     */
    public function storeUserShare(StoreUserDocumentShareRequest $request, Document $document): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasRole('super-admin') && $actor->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        Gate::forUser($actor)->authorize('share', $document);

        $targetUser = User::findOrFail($request->validated('user_id'));
        $permission = $request->validated('permission');
        $expiresAt = $request->filled('expires_at') ? Carbon::parse($request->validated('expires_at')) : null;

        $share = $this->shareService->shareWithUser($actor, $document, $targetUser, $permission, $expiresAt);

        return response()->json($share, 201);
    }

    /**
     * Share a document with a group.
     */
    public function storeGroupShare(StoreGroupDocumentShareRequest $request, Document $document): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasRole('super-admin') && $actor->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        Gate::forUser($actor)->authorize('share', $document);

        $group = Group::findOrFail($request->validated('group_id'));
        $permission = $request->validated('permission');
        $expiresAt = $request->filled('expires_at') ? Carbon::parse($request->validated('expires_at')) : null;

        $share = $this->shareService->shareWithGroup($actor, $document, $group, $permission, $expiresAt);

        return response()->json($share, 201);
    }

    /**
     * Revoke a document share.
     */
    public function destroy(Request $request, Document $document, DocumentShare $share): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasRole('super-admin') && $actor->organization_id !== $document->organization_id) {
            abort(403, 'You do not belong to this document\'s organization');
        }

        if ($share->document_id !== $document->id) {
            abort(403, 'This share does not belong to the specified document');
        }

        Gate::forUser($actor)->authorize('share', $document);

        $this->shareService->revoke($actor, $share);

        return response()->json(['message' => 'Share successfully revoked']);
    }
}
