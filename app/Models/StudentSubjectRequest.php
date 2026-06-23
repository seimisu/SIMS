<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSubjectRequest extends Model
{
    protected $connection = 'scholars';

    protected $table = 'student_subject_requests';

    public $timestamps = false;

    protected $fillable = [
        'student_subject_id',
        'subject_id',
        'reviewed_at',
        'reviewed_by',
        'status',
        'remarks',
    ];

    public function studentSubject()
    {
        return $this->belongsTo(StudentSubject::class, 'student_subject_id', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(SchoolCampusCourseCurriculumSubjects::class, 'subject_id');
    }
}
