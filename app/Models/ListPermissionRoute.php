<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListPermissionRoute extends Model
{
    protected $fillable = [
        'route_name',
        'permission_id',
    ];

    public function permission()
    {
        return $this->belongsTo(ListPermission::class, 'permission_id');
    }
}
