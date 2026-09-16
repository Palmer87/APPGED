<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocumentShare;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShareWebController extends Controller
{
    /**
     * Display list of documents shared with the user or by the user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $groupIds = $user->groups()->pluck('groups.id');

        // Shares directed to current user or their groups
        $receivedShares = DocumentShare::query()
            ->where('organization_id', $user->organization_id)
            ->active()
            ->where(function ($q) use ($user, $groupIds) {
                $q->where('user_id', $user->id);
                if ($groupIds->isNotEmpty()) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->with(['document.folder', 'document.creator', 'sharedBy', 'group'])
            ->latest()
            ->get();

        // Shares created by current user
        $sentShares = DocumentShare::query()
            ->where('organization_id', $user->organization_id)
            ->where('shared_by', $user->id)
            ->active()
            ->with(['document.folder', 'user', 'group'])
            ->latest()
            ->get();

        return Inertia::render('Shares/Index', [
            'receivedShares' => $receivedShares,
            'sentShares' => $sentShares,
        ]);
    }
}
