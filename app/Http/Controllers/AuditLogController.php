<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Display a listing of audit logs with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'organization_id',
            'user_id',
            'action',
            'result',
            'auditable_type',
            'auditable_id',
            'target_type',
            'target_id',
            'date_from',
            'date_to',
        ]);

        $perPage = (int) $request->input('per_page', AuditLogService::DEFAULT_PER_PAGE);

        $logs = $this->auditLogService->getLogs($request->user(), $filters, $perPage);

        return response()->json($logs);
    }

    /**
     * Display the chronological audit history for a specific document.
     */
    public function documentHistory(Request $request, Document $document): JsonResponse
    {
        $perPage = (int) $request->input('per_page', AuditLogService::DEFAULT_PER_PAGE);

        $history = $this->auditLogService->getDocumentHistory($request->user(), $document, $perPage);

        return response()->json($history);
    }
}
