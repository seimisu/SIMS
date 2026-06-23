<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchRecipients extends Model
{
    protected $fillable = [
        'batch_id',
        'scholar_id',
        'account_no',
        'birthday',
        'period',
        'scholarship_status',
        'total_stipend',
        'total_withheld',
        'learning_materials_amount',
        'clothing_amount',
        'grand_total',
        'remarks',
        'status',
    ];

    protected $casts = [
        'birthday' => 'date',
        'total_stipend' => 'decimal:2',
        'total_withheld' => 'decimal:2',
        'learning_materials_amount' => 'decimal:2',
        'clothing_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(Batches::class, 'batch_id');
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id');
    }

    public function stipends()
    {
        return $this->hasMany(RecipientStipend::class, 'recipient_id');
    }

    public function withhelds()
    {
        return $this->hasMany(RecipientWithheld::class, 'recipient_id');
    }
}
