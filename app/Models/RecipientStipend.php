<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientStipend extends Model
{
    protected $fillable = [
        'recipient_id',
        'amount',
        'month',
        'month_no',
        'status',
        'remarks',
        'credited_by',
        'credited_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credited_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(BatchRecipients::class, 'recipient_id');
    }

    public function creditedBy()
    {
        return $this->belongsTo(User::class, 'credited_by');
    }
}
