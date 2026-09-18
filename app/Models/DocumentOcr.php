<?php

namespace App\Models;

use App\Enums\OcrStatus;
use Database\Factories\DocumentOcrFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'document_id',
    'document_version_id',
    'status',
    'extracted_text',
    'error_message',
    'word_count',
    'confidence',
    'language',
    'execution_time_ms',
    'processed_at',
])]
class DocumentOcr extends Model
{
    /** @use HasFactory<DocumentOcrFactory> */
    use HasFactory;

    protected $casts = [
        'status' => OcrStatus::class,
        'word_count' => 'int',
        'confidence' => 'float',
        'execution_time_ms' => 'int',
        'processed_at' => 'datetime',
    ];

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

    public function hasText(): bool
    {
        return ! empty($this->extracted_text);
    }
}
