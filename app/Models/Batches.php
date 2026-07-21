<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Batches extends Model
{

    protected $fillable = [
        'name',
        'region',
        'academic_term',
        'term_id',
        'level_id',
        'school_year',
        'is_lock',
    ];

    public $timestamps = false;

    public function logs()
    {
        return $this->hasMany(BatchLogs::class, 'batch_id');
    }

    public function latestLog()
    {
        return $this->hasOne(BatchLogs::class, 'batch_id')->latestOfMany('created_at');
    }

    public function recipients()
    {
        return $this->hasMany(BatchRecipients::class, 'batch_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(PayrollBatchActivityLog::class, 'batch_id');
    }

    public function revisions()
    {
        return $this->hasMany(PayrollBatchRevision::class, 'batch_id');
    }

    public function term()
    {
        return $this->belongsTo(ListReferences::class, 'term_id');
    }

    public function level()
    {
        return $this->belongsTo(ListReferences::class, 'level_id');
    }
}
