<?php

namespace App\Http\Controllers;

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
     * Toggle favorite status for the specified document.
     */
    public function toggle(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        $isFavorite = $this->favoriteService->toggleFavorite($user, $document);

        return response()->json([
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Document ajouté aux favoris.' : 'Document retiré des favoris.',
            'favorites_count' => $this->favoriteService->getFavoriteCount($user),
        ]);
    }
}
