<?php

namespace App\Models;

use App\Enums\FolderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organization_id',
        'parent_id',
        'folder_type',
        'name',
        'description',
        'path',
        'created_by',
        'is_archived',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'folder_type' => FolderType::class,
        'is_archived' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Organization relationship.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Parent folder relationship.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Children folders relationship.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Creator (user) relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class);
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class)->whereNotNull('user_id');
    }

    public function groupPermissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class)->whereNotNull('group_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Documents categorized under this document type.
     */
    public function typedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'document_type_id');
    }

    /**
     * Associated metadata definitions for this document type folder.
     */
    public function metadataDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(
            MetadataDefinition::class,
            'folder_metadata_definition',
            'folder_id',
            'metadata_definition_id'
        )
            ->withPivot(['is_required', 'order'])
            ->withTimestamps()
            ->orderByPivot('order', 'asc');
    }

    /**
     * Determine if folder is a department (direction).
     */
    public function isDepartment(): bool
    {
        return $this->folder_type === FolderType::Department;
    }

    /**
     * Determine if folder is a document type.
     */
    public function isDocumentType(): bool
    {
        return $this->folder_type === FolderType::DocumentType;
    }

    /**
     * Determine if folder is standard.
     */
    public function isStandard(): bool
    {
        return $this->folder_type === FolderType::Standard;
    }

    /**
     * Find nearest DocumentType in hierarchy (self or ancestor).
     */
    public function getDocumentType(): ?self
    {
        if ($this->isDocumentType()) {
            return $this;
        }

        $cursor = $this->parent;
        while ($cursor) {
            if ($cursor->isDocumentType()) {
                return $cursor;
            }
            $cursor = $cursor->parent;
        }

        return null;
    }

    /**
     * Find root Department in hierarchy (self or ancestor).
     */
    public function getDepartment(): ?self
    {
        if ($this->isDepartment()) {
            return $this;
        }

        $cursor = $this->parent;
        $highestAncestor = null;

        while ($cursor) {
            if ($cursor->isDepartment()) {
                return $cursor;
            }
            $highestAncestor = $cursor;
            $cursor = $cursor->parent;
        }

        // Fallback: if highest ancestor has parent_id null, it acts as direction
        if ($highestAncestor && $highestAncestor->parent_id === null) {
            return $highestAncestor;
        }

        return null;
    }

    /**
     * Scopes
     */
    public function scopeDepartments(Builder $query): Builder
    {
        return $query->where('folder_type', FolderType::Department);
    }

    public function scopeDocumentTypes(Builder $query): Builder
    {
        return $query->where('folder_type', FolderType::DocumentType);
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query->where('folder_type', FolderType::Standard);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
