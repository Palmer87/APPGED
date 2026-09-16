<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organization_id',
        'parent_id',
        'name',
        'description',
        'path',
        'created_by',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_archived' => 'boolean',
    ];

    /**
     * Organization relationship.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Parent folder relationship.
     */
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Children folders relationship.
     */
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Creator (user) relationship.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function permissions()
    {
        return $this->hasMany(FolderPermission::class);
    }

    public function userPermissions()
    {
        return $this->hasMany(FolderPermission::class)->whereNotNull('user_id');
    }

    public function groupPermissions()
    {
        return $this->hasMany(FolderPermission::class)->whereNotNull('group_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
