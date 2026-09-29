<?php

namespace App\Services;

use App\Enums\FolderType;
use App\Models\Document;
use App\Models\DocumentOcr;
use App\Models\Folder;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionUsageService
{
    /**
     * Compute full usage breakdown for an organization compared against its subscription limits.
     *
     * @return array<string, mixed>
     */
    public function getUsage(Organization|int $organization, ?Plan $plan = null): array
    {
        $org = $organization instanceof Organization
            ? $organization
            : Organization::with(['currentSubscription.plan'])->findOrFail($organization);

        if (! $plan) {
            $plan = $org->currentSubscription?->plan
                ?? Plan::where('slug', 'essential')->first()
                ?? new Plan(['name' => 'Essentiel', 'slug' => 'essential', 'max_users' => 5]);
        }

        $usersCount = $this->getUsersCount($org);
        $storageBytes = $this->getStorageBytesUsed($org);
        $directionsCount = $this->getDirectionsCount($org);
        $documentTypesCount = $this->getDocumentTypesCount($org);
        $ocrPagesThisMonth = $this->getOcrPagesThisMonth($org);

        $users = $this->buildMetricUsage($usersCount, $plan->max_users, 'utilisateurs');
        $storage = $this->buildStorageMetricUsage($storageBytes, $plan->max_storage_bytes);
        $directions = $this->buildMetricUsage($directionsCount, $plan->max_directions, 'directions');
        $documentTypes = $this->buildMetricUsage($documentTypesCount, $plan->max_document_types, 'types documentaires');
        $ocr = $this->buildMetricUsage($ocrPagesThisMonth, $plan->max_ocr_pages_month, 'pages OCR');

        $isAnyExceeded = $users['is_exceeded']
            || $storage['is_exceeded']
            || $directions['is_exceeded']
            || $documentTypes['is_exceeded']
            || $ocr['is_exceeded'];

        $warnings = [];
        if ($storage['percentage'] >= 80 && ! $storage['is_exceeded']) {
            $warnings[] = [
                'type' => 'storage',
                'message' => "Votre stockage atteint {$storage['percentage']}% de votre limite autorisée.",
            ];
        }
        if ($users['percentage'] >= 80 && ! $users['is_exceeded']) {
            $warnings[] = [
                'type' => 'users',
                'message' => "Vous utilisez {$users['used']} sur {$users['limit']} utilisateurs autorisés.",
            ];
        }
        if ($ocr['percentage'] >= 80 && ! $ocr['is_exceeded']) {
            $warnings[] = [
                'type' => 'ocr',
                'message' => "Vous avez consommé {$ocr['used']} sur {$ocr['limit']} pages OCR ce mois.",
            ];
        }

        return [
            'plan_name' => $plan->name,
            'plan_slug' => $plan->slug,
            'is_any_exceeded' => $isAnyExceeded,
            'warnings' => $warnings,
            'metrics' => [
                'users' => $users,
                'storage' => $storage,
                'directions' => $directions,
                'document_types' => $documentTypes,
                'ocr' => $ocr,
            ],
        ];
    }

    public function getUsersCount(Organization $organization): int
    {
        return User::where('organization_id', $organization->id)->count();
    }

    public function getStorageBytesUsed(Organization $organization): int
    {
        return (int) Document::where('organization_id', $organization->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'archived')
            ->sum('size');
    }

    public function getDirectionsCount(Organization $organization): int
    {
        return Folder::where('organization_id', $organization->id)
            ->where('folder_type', FolderType::Department)
            ->count();
    }

    public function getDocumentTypesCount(Organization $organization): int
    {
        return Folder::where('organization_id', $organization->id)
            ->where('folder_type', FolderType::DocumentType)
            ->count();
    }

    public function getOcrPagesThisMonth(Organization $organization): int
    {
        $startOfMonth = now()->startOfMonth();

        return DocumentOcr::where('organization_id', $organization->id)
            ->where('created_at', '>=', $startOfMonth)
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildMetricUsage(int $used, ?int $limit, string $unit): array
    {
        $isUnlimited = $limit === null;
        $percentage = (! $isUnlimited && $limit > 0) ? min(100, round(($used / $limit) * 100, 1)) : 0;
        $isExceeded = (! $isUnlimited && $used > $limit);

        return [
            'used' => $used,
            'limit' => $limit,
            'is_unlimited' => $isUnlimited,
            'percentage' => $percentage,
            'is_exceeded' => $isExceeded,
            'formatted' => $isUnlimited ? "{$used} / Illimité" : "{$used} / {$limit}",
            'unit' => $unit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildStorageMetricUsage(int $usedBytes, ?int $limitBytes): array
    {
        $isUnlimited = $limitBytes === null;
        $percentage = (! $isUnlimited && $limitBytes > 0) ? min(100, round(($usedBytes / $limitBytes) * 100, 1)) : 0;
        $isExceeded = (! $isUnlimited && $usedBytes > $limitBytes);

        return [
            'used_bytes' => $usedBytes,
            'limit_bytes' => $limitBytes,
            'used_formatted' => $this->formatBytes($usedBytes),
            'limit_formatted' => $isUnlimited ? 'Illimité' : $this->formatBytes($limitBytes),
            'is_unlimited' => $isUnlimited,
            'percentage' => $percentage,
            'is_exceeded' => $isExceeded,
            'formatted' => $isUnlimited
                ? $this->formatBytes($usedBytes).' / Illimité'
                : $this->formatBytes($usedBytes).' / '.$this->formatBytes($limitBytes),
            'unit' => 'stockage',
        ];
    }

    public function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 Go';
        }

        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $base = log($bytes, 1024);
        $floor = (int) floor($base);

        $index = min($floor, count($units) - 1);
        $val = round(pow(1024, $base - $index), $precision);

        return "{$val} {$units[$index]}";
    }
}
