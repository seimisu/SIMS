<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Batches extends Model
{

    protected $fillable = [
        'name',
        'region',
        'academic_term',
        'school_year',
    ];

    public $timestamps = false;

    public function logs()
    {
        return $this->hasMany(BatchLogs::class, 'batch_id');
    }
}