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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function recipient()
    {
        return $this->belongsTo(BatchRecipients::class, 'recipient_id');
    }
}
