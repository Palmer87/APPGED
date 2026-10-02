<?php

namespace App\Services;

use App\Models\Direction;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;

class QuotaService
{
    /**
     * Get quota usage and limits for a specific organization.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationQuotas(Organization $organization): array
    {
        $subscription = $organization->currentSubscription;
        $plan = $subscription?->plan ?? Plan::where('slug', 'essential')->first();

        $currentUsers = User::where('organization_id', $organization->id)->count();
        $currentStorage = (int) Document::where('organization_id', $organization->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'archived')
            ->sum('size');
        $currentDirections = Direction::where('organization_id', $organization->id)->count();
        $currentDocTypes = Folder::where('organization_id', $organization->id)
            ->where('folder_type', 'document_type')
            ->count();

        $startOfMonth = now()->startOfMonth();
        $currentOcrPages = DocumentOcr::whereHas('document', function ($q) use ($organization) {
            $q->where('organization_id', $organization->id);
        })->where('created_at', '>=', $startOfMonth)
            ->where('status', 'completed')
            ->count();

        $maxUsers = $plan?->max_users;
        $maxStorage = $plan?->max_storage_bytes;
        $maxDirections = $plan?->max_directions;
        $maxDocTypes = $plan?->max_document_types;
        $maxOcrPages = $plan?->max_ocr_pages_month;

        return [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'plan_name' => $plan?->name ?? 'Aucun',
            'plan_slug' => $plan?->slug ?? 'none',
            'metrics' => [
                'users' => $this->formatMetric($currentUsers, $maxUsers, 'Utilisateurs'),
                'storage' => $this->formatStorageMetric($currentStorage, $maxStorage),
                'directions' => $this->formatMetric($currentDirections, $maxDirections, 'Directions'),
                'document_types' => $this->formatMetric($currentDocTypes, $maxDocTypes, 'Types documentaires'),
                'ocr' => $this->formatMetric($currentOcrPages, $maxOcrPages, 'Pages OCR ce mois'),
            ],
            'alerts' => $this->buildAlerts([
                'users' => $this->formatMetric($currentUsers, $maxUsers, 'Utilisateurs'),
                'storage' => $this->formatStorageMetric($currentStorage, $maxStorage),
                'directions' => $this->formatMetric($currentDirections, $maxDirections, 'Directions'),
                'document_types' => $this->formatMetric($currentDocTypes, $maxDocTypes, 'Types documentaires'),
                'ocr' => $this->formatMetric($currentOcrPages, $maxOcrPages, 'Pages OCR'),
            ]),
        ];
    }

    /**
     * Get global metrics across the whole SaaS platform.
     *
     * @return array<string, mixed>
     */
    public function getGlobalUsageMetrics(): array
    {
        $totalOrganizations = Organization::count();
        $activeOrganizations = Organization::where('status', 'active')->count();
        $suspendedOrganizations = Organization::where('status', 'suspended')->count();

        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();

        $totalDocuments = Document::count();
        $activeDocuments = Document::whereNull('deleted_at')->where('status', '!=', 'archived')->count();
        $archivedDocuments = Document::where('status', 'archived')->count();
        $trashedDocuments = Document::onlyTrashed()->count();

        $totalStorageBytes = (int) Document::whereNull('deleted_at')->sum('size');
        $averageStorageBytes = $totalOrganizations > 0 ? (int) ($totalStorageBytes / $totalOrganizations) : 0;

        $startOfMonth = now()->startOfMonth();
        $totalOcrPagesMonth = DocumentOcr::where('created_at', '>=', $startOfMonth)
            ->where('status', 'completed')
            ->count();
        $totalOcrPagesAllTime = DocumentOcr::where('status', 'completed')->count();

        return [
            'organizations' => [
                'total' => $totalOrganizations,
                'active' => $activeOrganizations,
                'suspended' => $suspendedOrganizations,
            ],
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
            ],
            'documents' => [
                'total' => $totalDocuments,
                'active' => $activeDocuments,
                'archived' => $archivedDocuments,
                'trashed' => $trashedDocuments,
            ],
            'storage' => [
                'total_bytes' => $totalStorageBytes,
                'total_formatted' => $this->formatBytes($totalStorageBytes),
                'average_bytes' => $averageStorageBytes,
                'average_formatted' => $this->formatBytes($averageStorageBytes),
            ],
            'ocr' => [
                'month_pages' => $totalOcrPagesMonth,
                'all_time_pages' => $totalOcrPagesAllTime,
            ],
        ];
    }

    private function formatMetric(int $used, ?int $limit, string $label): array
    {
        $percentage = ($limit && $limit > 0) ? min(100, (int) round(($used / $limit) * 100)) : 0;
        $isExceeded = $limit !== null && $used > $limit;

        return [
            'label' => $label,
            'used' => $used,
            'limit' => $limit,
            'is_unlimited' => $limit === null,
            'percentage' => $percentage,
            'is_exceeded' => $isExceeded,
        ];
    }

    private function formatStorageMetric(int $usedBytes, ?int $limitBytes): array
    {
        $percentage = ($limitBytes && $limitBytes > 0) ? min(100, (int) round(($usedBytes / $limitBytes) * 100)) : 0;
        $isExceeded = $limitBytes !== null && $usedBytes > $limitBytes;

        return [
            'label' => 'Stockage',
            'used' => $usedBytes,
            'used_formatted' => $this->formatBytes($usedBytes),
            'limit' => $limitBytes,
            'limit_formatted' => $limitBytes ? $this->formatBytes($limitBytes) : 'Illimité',
            'is_unlimited' => $limitBytes === null,
            'percentage' => $percentage,
            'is_exceeded' => $isExceeded,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', ' ').' Go';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', ' ').' Mo';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', ' ').' Ko';
        }

        return $bytes.' octets';
    }

    /**
     * @param  array<string, array<string, mixed>>  $metrics
     * @return array<int, array<string, mixed>>
     */
    private function buildAlerts(array $metrics): array
    {
        $alerts = [];
        foreach ($metrics as $key => $metric) {
            if ($metric['is_unlimited']) {
                continue;
            }
            if ($metric['percentage'] >= 100) {
                $alerts[] = [
                    'metric' => $key,
                    'level' => 'danger',
                    'percentage' => $metric['percentage'],
                    'message' => "Quota {$metric['label']} atteint à 100%.",
                ];
            } elseif ($metric['percentage'] >= 90) {
                $alerts[] = [
                    'metric' => $key,
                    'level' => 'warning',
                    'percentage' => $metric['percentage'],
                    'message' => "Attention : {$metric['label']} à {$metric['percentage']}% du quota.",
                ];
            } elseif ($metric['percentage'] >= 80) {
                $alerts[] = [
                    'metric' => $key,
                    'level' => 'info',
                    'percentage' => $metric['percentage'],
                    'message' => "{$metric['label']} à {$metric['percentage']}% du quota.",
                ];
            }
        }

        return $alerts;
    }
}
