<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarSchoolGrades extends Model
{
    protected $fillable = [
        'term_record_id',
        'subject_id',
        'remarks',
        'grade_id',
        'is_deleted',
    ];

    protected $hidden = ['grade_id', 'subject_id', 'term_record_id', 'created_at', 'updated_at'];

    public function termRecord()
    {
        return $this->belongsTo(ScholarTerm::class, 'term_record_id');
    }

    public function subject()
    {
        return $this->belongsTo(SchoolCampusCourseCurriculumSubjects::class, 'subject_id');
    }

    public function grade()
    {
        return $this->belongsTo(SchoolCampusGrades::class, 'grade_id');
    }

    public function gradeRequests()
    {
        return $this->hasManyThrough(
            StudentGradeRequest::class,
            StudentGrade::class,
            'term_record_id',      // Foreign key on StudentGrade
            'student_grades_id',   // Foreign key on StudentGradeRequest
            'term_record_id',      // Local key on ScholarSchoolGrades
            'id'                   // Local key on StudentGrade
        )->where('student_grade_requests.subject_id', $this->subject_id);
    }
}
