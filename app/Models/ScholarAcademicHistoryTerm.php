<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarAcademicHistoryTerm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'submission_id',
        'campus_id',
        'campus_course_id',
        'curriculum_id',
        'term_id',
        'level_id',
        'academic_year',
        'school_name',
        'course_name',
        'term_name',
        'level_name',
        'remarks',
    ];

    public function submission()
    {
        return $this->belongsTo(ScholarAcademicHistorySubmission::class, 'submission_id');
    }

    public function subjects()
    {
        return $this->hasMany(ScholarAcademicHistorySubject::class, 'history_term_id');
    }

    public function files()
    {
        return $this->hasMany(ScholarAcademicHistoryFile::class, 'history_term_id');
    }

    public function campus()
    {
        return $this->belongsTo(SchoolCampuses::class, 'campus_id');
    }

    public function course()
    {
        return $this->belongsTo(SchoolCampusCourses::class, 'campus_course_id');
    }

    public function curriculum()
    {
        return $this->belongsTo(SchoolCampusCourseCurriculums::class, 'curriculum_id');
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
