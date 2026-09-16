<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Services\RecentDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentDocumentController extends Controller
{
    public function __construct(
        protected RecentDocumentService $recentService
    ) {}

    /**
     * List recently accessed or updated documents for the user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = max(1, min((int) $request->input('limit', 10), 50));

        $documents = $this->recentService->getRecentDocuments($user, $limit);

        return response()->json([
            'data' => DocumentResource::collection($documents),
        ]);
    }
}
