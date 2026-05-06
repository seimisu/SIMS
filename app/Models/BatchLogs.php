<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchLogs extends Model
{

    protected $fillable = [
        'batch_id',
        'status',
        'remarks',
        'action_by'

    ];

    public function batch()
    {
        return $this->belongsTo(Batches::class, 'batch_id');
    }
}