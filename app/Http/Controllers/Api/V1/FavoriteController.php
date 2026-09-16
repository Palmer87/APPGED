<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        protected FavoriteService $favoriteService
    ) {}

    /**
     * List accessible favorite documents for the user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = max(1, min((int) $request->input('limit', 20), 100));

        $favorites = $this->favoriteService->getUserFavorites($user, $limit);

        return response()->json([
            'data' => DocumentResource::collection($favorites),
            'total' => $this->favoriteService->getFavoriteCount($user),
        ]);
    }

    /**
     * Toggle favorite status for a document.
     */
    public function toggle(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $isFavorite = $this->favoriteService->toggleFavorite($user, $document);

        return response()->json([
            'data' => [
                'document_id' => $document->id,
                'is_favorite' => $isFavorite,
            ],
            'message' => $isFavorite
                ? 'Document added to favorites.'
                : 'Document removed from favorites.',
        ]);
    }
}
