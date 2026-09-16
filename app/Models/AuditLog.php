<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Audit logs are strictly append-only (no updated_at).
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot model events to guarantee immutability.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    /**
     * The organization this audit log belongs to (nullable for system/global logs).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The user who performed the action (nullable).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation to the primary resource being acted upon (Document, Folder, etc.).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Polymorphic relation to the secondary target (User receiving a share, Group, etc.).
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
