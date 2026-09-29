<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    public $timestamps = false;
    protected $connection = 'scholars';
    protected $table = 'student_grades';

    protected $fillable = [
        'term_record_id',
        'grades_id',
        'grade_value',
    ];

    public function termRecord()
    {
        return $this->belongsTo(ScholarTerm::class, 'term_record_id');
    }

    public function grade()
    {
        return $this->belongsTo(SchoolCampusGrades::class, 'grades_id');
    }

}
