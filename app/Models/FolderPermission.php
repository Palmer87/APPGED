<?php

namespace App\Models;

use Database\Factories\FolderPermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['folder_id', 'user_id', 'group_id', 'permission'])]
class FolderPermission extends Model
{
    /** @use HasFactory<FolderPermissionFactory> */
    use HasFactory;

    protected $table = 'folder_permissions';

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
