<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarAcademicHistorySubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'scholar_id',
        'spas_no',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'return_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id');
    }

    public function terms()
    {
        return $this->hasMany(ScholarAcademicHistoryTerm::class, 'submission_id');
    }

    public function files()
    {
        return $this->hasMany(ScholarAcademicHistoryFile::class, 'submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
