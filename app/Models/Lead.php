<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'company',
    'email',
    'phone',
    'estimated_users',
    'needs',
    'message',
    'status',
    'ip_address',
    'metadata',
])]
class Lead extends Model
{
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];
}
