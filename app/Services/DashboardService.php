<?php

namespace App\Services;

use App\Enums\WorkflowApproverType;
use App\Enums\WorkflowStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use App\Models\WorkflowInstance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

class DashboardService
{
    public const ALLOWED_PERIODS = ['7d', '30d', '90d', '12m'];

    public function __construct(
        protected AccessControlService $aclService,
        protected FavoriteService $favoriteService,
        protected RecentDocumentService $recentDocumentService
    ) {}

    /**
     * Get the full aggregated dashboard payload for the user.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, string $period = '30d'): array
    {
        if ($user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        $validPeriod = in_array($period, self::ALLOWED_PERIODS, true) ? $period : '30d';

        $overview = $this->getOverviewStatistics($user);
        $recentDocs = $this->recentDocumentService->getRecentDocuments($user, 5);
        $favorites = $this->favoriteService->getUserFavorites($user, 5);
        $workflows = $this->getWorkflowSummary($user);
        $charts = $this->getChartData($user, $validPeriod, $overview);
        $recentActivity = $this->getRecentActivity($user, 8);
        $notifications = $this->getNotificationSummary($user);

        return [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
                'job_title' => $user->job_title,
                'role' => $user->roles->pluck('name')->first() ?? 'utilisateur',
            ],
            'organization' => [
                'id' => $user->organization?->id,
                'name' => $user->organization?->name,
                'storage_limit' => $user->organization?->storage_limit,
                'storage_limit_formatted' => $this->formatBytes($user->organization?->storage_limit ?? 0),
            ],
            'period' => $validPeriod,
            'statistics' => $overview,
            'recent_documents' => $this->formatDocuments($recentDocs),
            'favorites' => $this->formatDocuments($favorites),
            'workflows' => $workflows,
            'notifications' => $notifications,
            'recent_activity' => $recentActivity,
            'charts' => $charts,
        ];
    }

    /**
     * Compute overview KPI statistics.
     *
     * @return array<string, mixed>
     */
    public function getOverviewStatistics(User $user): array
    {
        // 1. Documents (Active & Archived) accessible to user
        $baseDocQuery = Document::query();
        $this->aclService->applyAccessScope($baseDocQuery, $user);

        $activeDocumentsCount = (clone $baseDocQuery)
            ->where('documents.status', '!=', 'archived')
            ->count();

        $archivedDocumentsCount = (clone $baseDocQuery)
            ->where('documents.status', 'archived')
            ->count();

        $storageUsed = (int) ((clone $baseDocQuery)
            ->where('documents.status', '!=', 'archived')
            ->sum('documents.size') ?? 0);

        // 2. Trashed documents in user's organization
        $trashedQuery = Document::onlyTrashed();
        if (! $user->hasRole('super-admin')) {
            $trashedQuery->where('documents.organization_id', $user->organization_id);
        }
        $trashedDocumentsCount = $trashedQuery->count();

        // 3. Folders accessible to user
        $foldersCount = $this->getAccessibleFoldersCount($user);

        // 4. Favorites count
        $favoritesCount = $this->favoriteService->getFavoriteCount($user);

        // 5. Workflows requiring user action
        $pendingMyActionCount = $this->getPendingActionWorkflowCount($user);

        // 6. Unread notifications
        $notificationsUnreadCount = $user->unreadNotifications()->count();

        return [
            'documents_count' => $activeDocumentsCount,
            'documents_archived_count' => $archivedDocumentsCount,
            'documents_trashed_count' => $trashedDocumentsCount,
            'folders_count' => $foldersCount,
            'favorites_count' => $favoritesCount,
            'workflows_pending_count' => $pendingMyActionCount,
            'notifications_unread_count' => $notificationsUnreadCount,
            'storage_used' => $storageUsed,
            'storage_used_formatted' => $this->formatBytes($storageUsed),
        ];
    }

    /**
     * Compute workflow indicators and pending items.
     *
     * @return array<string, mixed>
     */
    public function getWorkflowSummary(User $user): array
    {
        $userGroupIds = $user->groups()->pluck('groups.id')->all();

        // Workflows where current step requires action from $user or user's groups
        $pendingQuery = WorkflowInstance::query()
            ->where('workflow_instances.status', WorkflowStatus::InProgress)
            ->whereHas('currentStep', function (Builder $q) use ($user, $userGroupIds) {
                $q->where(function (Builder $sub) use ($user, $userGroupIds) {
                    $sub->where(function ($userSub) use ($user) {
                        $userSub->where('approver_type', WorkflowApproverType::User)
                            ->where('approver_user_id', $user->id);
                    });

                    if (! empty($userGroupIds)) {
                        $sub->orWhere(function ($groupSub) use ($userGroupIds) {
                            $groupSub->where('approver_type', WorkflowApproverType::Group)
                                ->whereIn('approver_group_id', $userGroupIds);
                        });
                    }
                });
            })
            ->with(['workflow', 'document', 'currentStep', 'startedBy']);

        if (! $user->hasRole('super-admin')) {
            $pendingQuery->where('workflow_instances.organization_id', $user->organization_id);
        }

        $pendingInstances = $pendingQuery->latest('workflow_instances.updated_at')->take(5)->get();

        // Filter pending instances so only accessible documents are displayed
        $filteredPending = $pendingInstances->filter(function (WorkflowInstance $instance) use ($user) {
            if (! $instance->document) {
                return false;
            }

            return Gate::forUser($user)->allows('view', $instance->document);
        });

        // In-progress count in organization
        $inProgressQuery = WorkflowInstance::query()
            ->where('status', WorkflowStatus::InProgress);

        if (! $user->hasRole('super-admin')) {
            $inProgressQuery->where('organization_id', $user->organization_id);
        }
        $inProgressCount = $inProgressQuery->count();

        return [
            'pending_my_action' => $filteredPending->map(fn (WorkflowInstance $instance) => [
                'id' => $instance->id,
                'workflow_name' => $instance->workflow?->name ?? 'Workflow',
                'document_id' => $instance->document_id,
                'document_name' => $instance->document?->name ?? 'Document',
                'current_step_name' => $instance->currentStep?->name ?? 'Étape en attente',
                'started_by' => $instance->startedBy ? trim("{$instance->startedBy->first_name} {$instance->startedBy->last_name}") : null,
                'started_at' => $instance->started_at?->toISOString(),
                'started_at_human' => $instance->started_at?->diffForHumans(),
            ])->values()->all(),
            'pending_count' => $filteredPending->count(),
            'in_progress_count' => $inProgressCount,
        ];
    }

    /**
     * Get count of pending workflows requiring user's action.
     */
    public function getPendingActionWorkflowCount(User $user): int
    {
        $userGroupIds = $user->groups()->pluck('groups.id')->all();

        $query = WorkflowInstance::query()
            ->where('workflow_instances.status', WorkflowStatus::InProgress)
            ->whereHas('currentStep', function (Builder $q) use ($user, $userGroupIds) {
                $q->where(function (Builder $sub) use ($user, $userGroupIds) {
                    $sub->where(function ($userSub) use ($user) {
                        $userSub->where('approver_type', WorkflowApproverType::User)
                            ->where('approver_user_id', $user->id);
                    });

                    if (! empty($userGroupIds)) {
                        $sub->orWhere(function ($groupSub) use ($userGroupIds) {
                            $groupSub->where('approver_type', WorkflowApproverType::Group)
                                ->whereIn('approver_group_id', $userGroupIds);
                        });
                    }
                });
            });

        if (! $user->hasRole('super-admin')) {
            $query->where('workflow_instances.organization_id', $user->organization_id);
        }

        return $query->get()->filter(function (WorkflowInstance $instance) use ($user) {
            return $instance->document && Gate::forUser($user)->allows('view', $instance->document);
        })->count();

    }

    /**
     * Get chart distribution and timeline data.
     *
     * @param  array<string, mixed>  $overview
     * @return array<string, mixed>
     */
    public function getChartData(User $user, string $period, array $overview): array
    {
        $startDate = match ($period) {
            '7d' => Carbon::now()->subDays(7)->startOfDay(),
            '90d' => Carbon::now()->subDays(90)->startOfDay(),
            '12m' => Carbon::now()->subMonths(12)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        // Query accessible documents for types & timeline
        $docQuery = Document::query()
            ->whereNull('documents.deleted_at')
            ->where('documents.status', '!=', 'archived')
            ->select(['documents.id', 'documents.extension', 'documents.mime_type', 'documents.created_at']);

        $this->aclService->applyAccessScope($docQuery, $user);

        $documents = $docQuery->get();

        // 1. By Type (PDF, Images, DOCX, XLSX, Autres)
        $byType = [
            'pdf' => 0,
            'images' => 0,
            'docx' => 0,
            'xlsx' => 0,
            'autres' => 0,
        ];

        foreach ($documents as $doc) {
            $ext = strtolower((string) $doc->extension);
            $mime = strtolower((string) $doc->mime_type);

            if ($ext === 'pdf' || str_contains($mime, 'pdf')) {
                $byType['pdf']++;
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true) || str_starts_with($mime, 'image/')) {
                $byType['images']++;
            } elseif (in_array($ext, ['doc', 'docx'], true) || str_contains($mime, 'word')) {
                $byType['docx']++;
            } elseif (in_array($ext, ['xls', 'xlsx', 'csv'], true) || str_contains($mime, 'sheet') || str_contains($mime, 'excel')) {
                $byType['xlsx']++;
            } else {
                $byType['autres']++;
            }
        }

        // 2. By Status (Active, Archived, Trash)
        $byStatus = [
            'active' => $overview['documents_count'],
            'archived' => $overview['documents_archived_count'],
            'trash' => $overview['documents_trashed_count'],
        ];

        // 3. Timeline over selected period
        $timeline = $this->buildTimeline($documents, $period, $startDate);

        return [
            'by_type' => $byType,
            'by_status' => $byStatus,
            'timeline' => $timeline,
        ];
    }

    /**
     * Get recent audit activities for user's organization.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentActivity(User $user, int $limit = 8): array
    {
        $limit = max(1, min($limit, 25));

        $query = AuditLog::query()
            ->with(['user'])
            ->latest('created_at')
            ->take($limit);

        if (! $user->hasRole('super-admin')) {
            $query->where('organization_id', $user->organization_id);
        }

        return $query->get()->map(function (AuditLog $log) {
            $userName = $log->user ? trim("{$log->user->first_name} {$log->user->last_name}") : 'Système';

            return [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $this->formatActionLabel($log->action),
                'result' => $log->result,
                'description' => $log->description ?? $log->action,
                'user_name' => $userName,
                'created_at' => $log->created_at?->toISOString(),
                'created_at_human' => $log->created_at?->diffForHumans(),
            ];
        })->all();
    }

    /**
     * Get summary of latest notifications.
     *
     * @return array<string, mixed>
     */
    public function getNotificationSummary(User $user): array
    {
        $latest = $user->notifications()
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'is_read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->toISOString(),
                    'created_at_human' => $notification->created_at?->diffForHumans(),
                ];
            })->all();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $latest,
        ];
    }

    /**
     * Count accessible folders for the user.
     */
    protected function getAccessibleFoldersCount(User $user): int
    {
        if ($user->hasRole('super-admin')) {
            return Folder::count();
        }

        if (! $user->can('folders.view')) {
            return 0;
        }

        $userGroupIds = $user->groups()->pluck('groups.id')->all();

        return Folder::query()
            ->where('organization_id', $user->organization_id)
            ->where(function (Builder $query) use ($user, $userGroupIds) {
                // Folder without specific ACL
                $query->whereDoesntHave('permissions')
                    // OR Folder with user view ACL
                    ->orWhereHas('permissions', function (Builder $q) use ($user) {
                        $q->where('permission', 'view')->where('user_id', $user->id);
                    });

                // OR Folder with group view ACL
                if (! empty($userGroupIds)) {
                    $query->orWhereHas('permissions', function (Builder $q) use ($userGroupIds) {
                        $q->where('permission', 'view')->whereIn('group_id', $userGroupIds);
                    });
                }
            })
            ->count();
    }

    /**
     * Build timeline data points across the chosen date period.
     *
     * @param  Collection<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    protected function buildTimeline(Collection $documents, string $period, Carbon $startDate): array
    {
        $points = [];

        if ($period === '12m') {
            // Monthly bucketing (last 12 months)
            for ($i = 11; $i >= 0; $i--) {
                $monthDate = Carbon::now()->subMonths($i);
                $key = $monthDate->format('Y-m');
                $label = $monthDate->translatedFormat('M Y');
                $points[$key] = ['label' => $label, 'count' => 0];
            }

            foreach ($documents as $doc) {
                if ($doc->created_at && $doc->created_at->gte($startDate)) {
                    $key = $doc->created_at->format('Y-m');
                    if (isset($points[$key])) {
                        $points[$key]['count']++;
                    }
                }
            }
        } else {
            // Daily / weekly bucketing
            $days = match ($period) {
                '7d' => 7,
                '90d' => 90,
                default => 30,
            };

            $step = $period === '90d' ? 5 : 1; // Sample every 5 days for 90d to prevent chart crowding

            for ($i = $days; $i >= 0; $i -= $step) {
                $date = Carbon::now()->subDays($i);
                $key = $date->format('Y-m-d');
                $label = $date->format('d/m');
                $points[$key] = ['label' => $label, 'count' => 0];
            }

            foreach ($documents as $doc) {
                if ($doc->created_at && $doc->created_at->gte($startDate)) {
                    $docDate = $doc->created_at->format('Y-m-d');
                    if (isset($points[$docDate])) {
                        $points[$docDate]['count']++;
                    } else {
                        // Nearest available sample bucket
                        foreach ($points as $k => &$p) {
                            if ($k >= $docDate) {
                                $p['count']++;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return array_values($points);
    }

    /**
     * Format a collection of Document models for frontend presentation.
     *
     * @param  Collection<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    protected function formatDocuments(Collection $documents): array
    {
        return $documents->map(fn (Document $doc) => [
            'id' => $doc->id,
            'name' => $doc->name,
            'file_name' => $doc->file_name,
            'extension' => strtolower((string) $doc->extension),
            'mime_type' => $doc->mime_type,
            'size' => $doc->size,
            'size_formatted' => $this->formatBytes($doc->size ?? 0),
            'folder_id' => $doc->folder_id,
            'folder_name' => $doc->folder?->name,
            'updated_at' => $doc->updated_at?->toISOString(),
            'updated_at_human' => $doc->updated_at?->diffForHumans(),
        ])->all();
    }

    /**
     * Convert bytes into a clean, readable human string.
     */
    public function formatBytes(int|float $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 o';
        }

        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), $precision).' '.$units[$power];
    }

    /**
     * User-friendly label for audit action keys.
     */
    protected function formatActionLabel(string $action): string
    {
        return match ($action) {
            'document.created' => 'Document créé',
            'document.updated' => 'Document modifié',
            'document.deleted' => 'Document supprimé',
            'document.previewed' => 'Document prévisualisé',
            'document.viewed' => 'Document consulté',
            'document.shared' => 'Document partagé',
            'document.archived' => 'Document archivé',
            'document.restored' => 'Document restauré',
            'document.comment_created' => 'Commentaire ajouté',
            'workflow.approved' => 'Workflow approuvé',
            'workflow.rejected' => 'Workflow rejeté',
            'folder.created' => 'Dossier créé',
            default => ucfirst(str_replace(['.', '_'], ' ', $action)),
        };
    }
}
