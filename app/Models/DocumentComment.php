<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeForDocument(Builder $query, int $documentId): Builder
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForVersion(Builder $query, int $versionId): Builder
    {
        return $query->where('document_version_id', $versionId);
    }

    public function getDisplayContentAttribute(): string
    {
        return $this->trashed() ? '[Commentaire supprimé]' : $this->content;
    }
}
