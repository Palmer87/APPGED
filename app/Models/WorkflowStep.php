<?php

namespace App\Models;

use App\Enums\WorkflowApproverType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'position' => 'integer',
        'is_required' => 'boolean',
        'approver_type' => WorkflowApproverType::class,
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function approverGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'approver_group_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'current_step_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_step_id');
    }
}
