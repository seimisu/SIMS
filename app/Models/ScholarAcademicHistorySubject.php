<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarAcademicHistorySubject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'history_term_id',
        'matched_subject_id',
        'subject_name',
        'subject_code',
        'subject_class',
        'unit',
        'grade',
        'is_failed',
        'is_incomplete',
        'is_dropped',
        'remarks',
    ];

    protected $casts = [
        'unit' => 'decimal:2',
        'is_failed' => 'boolean',
        'is_incomplete' => 'boolean',
        'is_dropped' => 'boolean',
    ];

    public function term()
    {
        return $this->belongsTo(ScholarAcademicHistoryTerm::class, 'history_term_id');
    }

    public function matchedSubject()
    {
        return $this->belongsTo(SchoolCampusCourseCurriculumSubjects::class, 'matched_subject_id');
    }
}
