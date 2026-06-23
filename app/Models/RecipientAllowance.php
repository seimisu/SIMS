<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientAllowance extends Model
{
    protected $fillable = [
        'recipient_id',
        'allowance_type_id',
        'classification',
        'amount',
        'remarks',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function recipient()
    {
        return $this->belongsTo(BatchRecipients::class, 'recipient_id');
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
