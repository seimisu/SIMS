<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarSchoolGrades extends Model
{
    protected $fillable = [
        'term_record_id',
        'subject_id',
        'remarks',
        'input_grade',
        'grade_id',
        'is_incomplete',
        'is_drop',
        'is_withdrawn',
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

}
