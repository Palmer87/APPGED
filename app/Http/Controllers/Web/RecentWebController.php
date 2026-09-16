<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\RecentDocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecentWebController extends Controller
{
    public function __construct(
        protected RecentDocumentService $recentService
    ) {}

    /**
     * List user recent documents.
     */
    public function index(Request $request): Response
    {
        $recentDocs = $this->recentService->getRecentDocuments($request->user(), 30);

        return Inertia::render('Recent/Index', [
            'recentDocuments' => $recentDocs,
        ]);
    }
}
