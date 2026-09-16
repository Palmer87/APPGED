<?php

namespace App\Enums;

enum WorkflowActionType: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case CorrectionRequested = 'correction_requested';
    case Resubmitted = 'resubmitted';
    case Cancelled = 'cancelled';
}
