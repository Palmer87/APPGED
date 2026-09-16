<?php

namespace App\Models;

use Database\Factories\MetadataDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'organization_id',
    'name',
    'key',
    'type',
    'description',
    'is_required',
    'is_active',
])]
class MetadataDefinition extends Model
{
    /** @use HasFactory<MetadataDefinitionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(DocumentMetadata::class, 'metadata_definition_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_metadata',
            'metadata_definition_id',
            'document_id'
        );
    }
}
