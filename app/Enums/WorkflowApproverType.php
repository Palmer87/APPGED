<?php

namespace App\Enums;

enum WorkflowApproverType: string
{
    case User = 'user';
    case Group = 'group';
}
