<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolCampusGrades extends Model
{

    protected $connection = 'pgsql';
    protected $fillable = [
        'campus_id',
        'grade',
        'lower',
        'upper',
        'is_active',
        'is_drop',
        'is_withdrawn',
        'is_delete',
        'is_failed',
        'is_incomplete'
    ];

    public function SchoolCampus()
    {
        return $this->belongsTo(SchoolCampuses::class, 'campus_id', 'id');
    }
}
