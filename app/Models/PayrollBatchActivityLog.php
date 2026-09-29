<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollBatchActivityLog extends Model
{
    protected $fillable = [
        'batch_id',
        'batch_recipient_id',
        'scholar_id',
        'action',
        'old_status',
        'new_status',
        'remarks',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(Batches::class, 'batch_id');
    }

    public function recipient()
    {
        return $this->belongsTo(BatchRecipients::class, 'batch_recipient_id');
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id');
    }
}
