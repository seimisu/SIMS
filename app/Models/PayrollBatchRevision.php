<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollBatchRevision extends Model
{
    protected $fillable = [
        'batch_id',
        'revision_no',
        'recipients_snapshot',
        'totals_snapshot',
        'payroll_file_path',
        'payroll_file_name',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'recipients_snapshot' => 'array',
        'totals_snapshot' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batches::class, 'batch_id');
    }

    public function recipients()
    {
        return $this->hasMany(PayrollBatchRevisionRecipient::class, 'payroll_batch_revision_id');
    }
}
