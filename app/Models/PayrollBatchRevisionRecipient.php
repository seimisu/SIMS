<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollBatchRevisionRecipient extends Model
{
    protected $fillable = [
        'payroll_batch_revision_id',
        'batch_id',
        'batch_recipient_id',
        'scholar_id',
        'row_payload',
        'is_for_removal',
        'marked_for_removal_by',
        'marked_for_removal_at',
    ];

    protected $casts = [
        'row_payload' => 'array',
        'is_for_removal' => 'boolean',
        'marked_for_removal_at' => 'datetime',
    ];

    public function revision()
    {
        return $this->belongsTo(PayrollBatchRevision::class, 'payroll_batch_revision_id');
    }
}

