<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\FavoriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteWebController extends Controller
{
    public function __construct(
        protected FavoriteService $favoriteService
    ) {}

    /**
     * List user favorite documents.
     */
    public function index(Request $request): Response
    {
        $favorites = $this->favoriteService->getUserFavorites($request->user(), 50);

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle favorite status.
     */
    public function toggle(Request $request, Document $document): RedirectResponse
    {
        $added = $this->favoriteService->toggleFavorite($request->user(), $document);
        $message = $added ? 'Document ajouté aux favoris.' : 'Document retiré des favoris.';

        return back()->with('success', $message);
    }
}
