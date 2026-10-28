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
        'deposit_status',
        'deposited_by',
        'deposited_at',
        'deposit_remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deposited_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(BatchRecipients::class, 'recipient_id');
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }

    public function depositedBy()
    {
        return $this->belongsTo(User::class, 'deposited_by');
    }
}
