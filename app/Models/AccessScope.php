<?php

namespace App\Models;

use App\Enums\AccessScopeType;
use Database\Factories\AccessScopeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessScope extends Model
{
    /** @use HasFactory<AccessScopeFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'scope_type',
        'direction_id',
        'service_id',
        'folder_id',
        'document_id',
        'is_active',
    ];

    protected $casts = [
        'scope_type' => AccessScopeType::class,
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function getTargetNameAttribute(): string
    {
        return match ($this->scope_type) {
            AccessScopeType::Organization => $this->organization?->name ?? 'Organisation entière',
            AccessScopeType::Direction => $this->direction?->name ?? 'Direction',
            AccessScopeType::Service => $this->service?->name ?? 'Service',
            AccessScopeType::DocumentType, AccessScopeType::Folder => $this->folder?->name ?? 'Dossier / Type',
            AccessScopeType::Document => $this->document?->name ?? 'Document',
            default => 'Périmètre',
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
