<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarAcademicHistoryFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'submission_id',
        'history_term_id',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'uploaded_by',
    ];

    public function submission()
    {
        return $this->belongsTo(ScholarAcademicHistorySubmission::class, 'submission_id');
    }

    public function term()
    {
        return $this->belongsTo(ScholarAcademicHistoryTerm::class, 'history_term_id');
    }
}
