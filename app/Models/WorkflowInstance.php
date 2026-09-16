<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => WorkflowStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WorkflowStatus::Pending,
            WorkflowStatus::InProgress,
            WorkflowStatus::CorrectionRequested,
        ]);
    }
}
