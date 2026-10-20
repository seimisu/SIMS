<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_incomplete',
    ];

    public function SchoolCampus()
    {
        return $this->campus();
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(SchoolCampuses::class, 'campus_id', 'id');
    }
}
