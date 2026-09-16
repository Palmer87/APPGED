<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationPreferenceService
{
    /**
     * Supported notification types.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_TYPES = [
        'document.shared',
        'document.version_created',
        'document.share_revoked',
        'document.commented',
        'document.workflow',
        'document.archived',
        'document.restored',
    ];

    /**
     * Get all notification preferences for a user.
     *
     * @return Collection<int, NotificationPreference>
     */
    public function getPreferences(User $user): Collection
    {
        return NotificationPreference::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->get();
    }

    /**
     * Update or create a preference for a specific notification type.
     */
    public function updatePreference(
        User $user,
        string $notificationType,
        bool $databaseEnabled,
        bool $emailEnabled
    ): NotificationPreference {
        if (! in_array($notificationType, self::SUPPORTED_TYPES, true)) {
            abort(422, "Unsupported notification type '{$notificationType}'");
        }

        return NotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
            ],
            [
                'organization_id' => $user->organization_id,
                'database_enabled' => $databaseEnabled,
                'email_enabled' => $emailEnabled,
            ]
        );
    }

    /**
     * Check if database notification is enabled for the user and notification type.
     */
    public function isDatabaseEnabled(User $user, string $notificationType): bool
    {
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->first();

        // Default is true if no explicit preference set
        return $preference ? (bool) $preference->database_enabled : true;
    }

    /**
     * Check if email notification is enabled for the user and notification type.
     */
    public function isEmailEnabled(User $user, string $notificationType): bool
    {
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->first();

        // Default is false if no explicit preference set
        return $preference ? (bool) $preference->email_enabled : false;
    }
}
