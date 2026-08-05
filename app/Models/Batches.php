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
        'school_year',
        'is_lock',
        'status',
        'generated_excel_path',
        'generated_excel_name',
        'generated_excel_at',
        'source',
        'is_historical',
        'imported_by',
        'imported_at',
        'import_file_path',
        'import_file_name',
    ];

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

}
