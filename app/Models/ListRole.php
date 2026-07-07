<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ListRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_delete',
        'is_lock',
        'created_by',
        'updated_by'
    ];
    protected $appends = ['formatted_date'];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }



    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->created_at)->format('M d, Y | h:i a');
    }

    public function routes()
    {
        return $this->hasMany(ListRoutes::class, 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(ListPermission::class, 'list_role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }
}
