<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PlatformAuditService
{
    /**
     * Record a platform audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        ?string $description = null,
        ?Model $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $organizationId = null,
        ?PlatformUser $actor = null
    ): PlatformAuditLog {
        /** @var PlatformUser|null $user */
        $user = $actor ?? Auth::guard('platform')->user();

        // Sanitize sensitive fields from old and new values
        $cleanedOld = $oldValues ? $this->sanitizeValues($oldValues) : null;
        $cleanedNew = $newValues ? $this->sanitizeValues($newValues) : null;

        return PlatformAuditLog::create([
            'platform_user_id' => $user?->id,
            'organization_id' => $organizationId,
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->getKey(),
            'description' => $description,
            'old_values' => $cleanedOld,
            'new_values' => $cleanedNew,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Strip passwords, tokens and secrets from audit values.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function sanitizeValues(array $values): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'remember_token',
            'api_token', 'secret', 'access_token', 'refresh_token',
            'r2_secret', 'r2_key', 'private_key',
        ];

        foreach ($values as $key => $val) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                $values[$key] = '***REDACTED***';
            } elseif (is_array($val)) {
                $values[$key] = $this->sanitizeValues($val);
            }
        }

        return $values;
    }
}
