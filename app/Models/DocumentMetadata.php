<?php

namespace App\Models;

use Database\Factories\DocumentMetadataFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'metadata_definition_id',
    'value_string',
    'value_text',
    'value_integer',
    'value_decimal',
    'value_boolean',
    'value_date',
    'value_datetime',
])]
class DocumentMetadata extends Model
{
    /** @use HasFactory<DocumentMetadataFactory> */
    use HasFactory;

    protected $table = 'document_metadata';

    protected function casts(): array
    {
        return [
            'value_integer' => 'integer',
            'value_decimal' => 'float',
            'value_boolean' => 'boolean',
            'value_date' => 'date:Y-m-d',
            'value_datetime' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MetadataDefinition::class, 'metadata_definition_id');
    }

    /**
     * Get normalized typed value based on definition type.
     */
    public function getTypedValue(): mixed
    {
        $type = $this->definition?->type;

        return match ($type) {
            'string' => $this->value_string,
            'text' => $this->value_text,
            'integer' => $this->value_integer !== null ? (int) $this->value_integer : null,
            'decimal' => $this->value_decimal !== null ? (float) $this->value_decimal : null,
            'boolean' => $this->value_boolean !== null ? (bool) $this->value_boolean : null,
            'date' => $this->value_date instanceof \DateTimeInterface ? $this->value_date->format('Y-m-d') : $this->value_date,
            'datetime' => $this->value_datetime instanceof \DateTimeInterface ? $this->value_datetime->format('Y-m-d H:i:s') : $this->value_datetime,
            default => $this->value_string
                ?? $this->value_text
                ?? $this->value_integer
                ?? $this->value_decimal
                ?? $this->value_boolean
                ?? $this->value_date
                ?? $this->value_datetime,
        };
    }
}
