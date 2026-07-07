<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListPermission extends Model
{
    protected $fillable = [
        'name',
        'label',
        'group_name',
        'description',
        'is_active',
    ];

    public function roles()
    {
        return $this->belongsToMany(ListRole::class, 'list_role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }

    public function routes()
    {
        return $this->hasMany(ListPermissionRoute::class, 'permission_id');
    }
}
