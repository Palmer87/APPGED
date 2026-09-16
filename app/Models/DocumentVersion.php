<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'document_id',
    'uploaded_by',
    'version_number',
    'file_name',
    'mime_type',
    'extension',
    'size',
    'storage_disk',
    'storage_path',
    'comment',
])]
class DocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $casts = [
        'version_number' => 'int',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class);
    }
}
