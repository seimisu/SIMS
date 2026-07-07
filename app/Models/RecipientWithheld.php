<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientWithheld extends Model
{
    protected $fillable = [
        'recipient_id',
        'month_no',
        'total_amount',
        'remarks',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function recipient()
    {
        return $this->belongsTo(BatchRecipients::class, 'recipient_id');
    }
}
