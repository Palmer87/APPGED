<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class PlatformUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'platform_users';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'platform_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class, 'platform_user_id');
    }

    public function isOwner(): bool
    {
        return $this->role === 'platform_owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin'], true);
    }

    public function isSupport(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_support'], true);
    }

    public function isBilling(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_billing'], true);
    }

    /**
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $roles = (array) $roles;

        return in_array($this->role, $roles, true);
    }

    public function canManageOrganizations(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_support'], true);
    }

    public function canSuspendOrganizations(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin'], true);
    }

    public function canManagePlans(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_billing'], true);
    }

    public function canManageSubscriptions(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_billing'], true);
    }

    public function canManageBilling(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_billing'], true);
    }

    public function canManageSupport(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin', 'platform_support'], true);
    }

    public function canViewAudit(): bool
    {
        return in_array($this->role, ['platform_owner', 'platform_admin'], true);
    }
}
