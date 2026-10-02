<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'platform_user_id',
    'organization_id',
    'action',
    'target_type',
    'target_id',
    'description',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
    'created_at',
])]
class PlatformAuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'platform_audit_logs';

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function platformUser(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
