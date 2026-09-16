<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'folder_id',
    'uploaded_by',
    'name',
    'description',
    'file_name',
    'mime_type',
    'extension',
    'size',
    'storage_disk',
    'storage_path',
    'status',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'document_category');
    }

    public function category(): ?Category
    {
        return $this->categories()->first();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tag');
    }

    public function metadataValues(): HasMany
    {
        return $this->hasMany(DocumentMetadata::class);
    }

    public function metadataDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(
            MetadataDefinition::class,
            'document_metadata',
            'document_id',
            'metadata_definition_id'
        );
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class);
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class)->whereNotNull('user_id');
    }

    public function groupPermissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class)->whereNotNull('group_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function userShares(): HasMany
    {
        return $this->hasMany(DocumentShare::class)->whereNotNull('user_id');
    }

    public function groupShares(): HasMany
    {
        return $this->hasMany(DocumentShare::class)->whereNotNull('group_id');
    }

    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function activeWorkflowInstance(): HasOne
    {
        return $this->hasOne(WorkflowInstance::class)->whereIn('status', [
            WorkflowStatus::Pending,
            WorkflowStatus::InProgress,
            WorkflowStatus::CorrectionRequested,
        ]);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class);
    }

    public function rootComments(): HasMany
    {
        return $this->hasMany(DocumentComment::class)->whereNull('parent_id');
    }
}
