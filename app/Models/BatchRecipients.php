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
        'is_for_removal_from_payroll',
        'marked_for_removal_by',
        'marked_for_removal_at',
        'moved_from_batch_id',
        'moved_from_batch_name',
        'moved_from_reason',
        'moved_from_marked_by',
        'moved_from_marked_at',
        'moved_notice_cleared_at',
    ];

    protected $casts = [
        'birthday' => 'date',
        'is_for_removal_from_payroll' => 'boolean',
        'marked_for_removal_at' => 'datetime',
        'moved_from_marked_at' => 'datetime',
        'moved_notice_cleared_at' => 'datetime',
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

    public function logs()
    {
        return $this->hasMany(BatchLogs::class, 'batch_id', 'batch_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(PayrollBatchActivityLog::class, 'batch_recipient_id');
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

    public function allowances()
    {
        return $this->hasMany(RecipientAllowance::class, 'recipient_id');
    }
}
