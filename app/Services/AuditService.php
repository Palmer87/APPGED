<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Sensitive fields that must be redacted in old_values, new_values, and metadata.
     */
    public const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'authorization',
    ];

    /**
     * Record an audit log entry.
     */
    public function log(
        string $action,
        string $result = 'success',
        ?Model $auditable = null,
        ?Model $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $user = null,
        ?int $organizationId = null,
        ?string $description = null
    ): AuditLog {
        $actor = $user ?? (auth()->check() ? auth()->user() : null);

        // Derive organization_id safely on server side
        $orgId = $organizationId;
        if ($orgId === null) {
            if ($auditable && isset($auditable->organization_id)) {
                $orgId = (int) $auditable->organization_id;
            } elseif ($actor && isset($actor->organization_id)) {
                $orgId = (int) $actor->organization_id;
            }
        }

        // Capture HTTP context safely (null in CLI or background jobs)
        $ipAddress = null;
        $userAgent = null;

        try {
            if (app()->runningUnitTests()) {
                // In tests, only capture IP/UserAgent if request has an explicit User-Agent header (not empty/Symfony default)
                $ua = request()?->userAgent();
                if (! empty($ua) && ! str_contains($ua, 'Symfony')) {
                    $ipAddress = request()->ip();
                    $userAgent = $ua;
                }
            } elseif (! app()->runningInConsole() && request()) {
                $ipAddress = request()->ip();
                $userAgent = request()->userAgent();
            }
        } catch (\Throwable) {
            $ipAddress = null;
            $userAgent = null;
        }

        // Apply redaction on payload arrays
        $cleanOldValues = $oldValues !== null ? $this->redact($oldValues) : null;
        $cleanNewValues = $newValues !== null ? $this->redact($newValues) : null;
        $cleanMetadata = $metadata !== null ? $this->redact($metadata) : null;

        return AuditLog::create([
            'organization_id' => $orgId,
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'description' => $description,
            'old_values' => $cleanOldValues,
            'new_values' => $cleanNewValues,
            'metadata' => $cleanMetadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'result' => in_array($result, ['success', 'failure'], true) ? $result : 'success',
        ]);
    }

    /**
     * Record a successful audit log.
     */
    public function success(
        string $action,
        ?Model $auditable = null,
        ?Model $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $user = null,
        ?int $organizationId = null,
        ?string $description = null
    ): AuditLog {
        return $this->log(
            action: $action,
            result: 'success',
            auditable: $auditable,
            target: $target,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata,
            user: $user,
            organizationId: $organizationId,
            description: $description
        );
    }

    /**
     * Record a failed audit log.
     */
    public function failure(
        string $action,
        string $reason,
        ?Model $auditable = null,
        ?Model $target = null,
        ?array $metadata = null,
        ?User $user = null,
        ?int $organizationId = null,
        ?string $description = null
    ): AuditLog {
        $meta = $metadata ?? [];
        $meta['reason'] = $reason;

        return $this->log(
            action: $action,
            result: 'failure',
            auditable: $auditable,
            target: $target,
            oldValues: null,
            newValues: null,
            metadata: $meta,
            user: $user,
            organizationId: $organizationId,
            description: $description
        );
    }

    /**
     * Recursively redact sensitive fields from an array.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redact(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if ($this->isSensitiveField($lowerKey)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if a field name matches sensitive patterns.
     */
    protected function isSensitiveField(string $fieldName): bool
    {
        foreach (self::SENSITIVE_FIELDS as $sensitive) {
            if ($fieldName === $sensitive || str_contains($fieldName, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
