<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchLogs extends Model
{
    protected $table = 'batches_logs';

    protected $fillable = [
        'batch_id',
        'status',
        'remarks',
        'payroll_file_path',
        'payroll_file_name',
        'action_by'

    ];

    public function batch()
    {
        return $this->belongsTo(Batches::class, 'batch_id');
    }
}
