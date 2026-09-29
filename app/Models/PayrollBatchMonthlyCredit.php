<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollBatchMonthlyCredit extends Model
{
    protected $fillable = [
        'batch_id',
        'month_no',
        'status',
        'amount',
        'recipient_count',
        'credited_by',
        'credited_at',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credited_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batches::class, 'batch_id');
    }

    public function creditedBy()
    {
        return $this->belongsTo(User::class, 'credited_by');
    }
}
