<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGradeRequest extends Model
{
    public $timestamps = false;
    protected $connection = 'scholars';
    protected $table = 'student_grade_requests';

    protected $fillable = [
        'student_grades_id',
        'subject_id',
        'grades_id',
        'reviewed_at',
        'reviewed_by',
        'status',
        'remarks',
    ];

    public function studentGrade()
    {
        return $this->belongsTo(StudentGrade::class, 'student_grades_id', 'id');
    }

    public function grade()
    {
        return $this->belongsTo(SchoolCampusGrades::class, 'grades_id');
    }

    public function subject()
    {
        return $this->belongsTo(SchoolCampusCourseCurriculumSubjects::class, 'subject_id');
    }

    public function termRecord()
    {
        return $this->studentGrade->termRecord();
    }
}
