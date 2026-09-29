<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolCampusCourseCurriculums extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'school_campus_course_curriculums';

    protected $fillable = [
        'campus_course_id',
        'semester_type_id',
        'years',
        'elective_limit',
        'total_year',
        'created_by',
        'updated_by',
        'is_duplicated',
        'is_active',
        'is_delete',
    ];

    protected $casts = [
        'total_year' => 'integer',
        'elective_limit' => 'integer',
    ];

    protected function semester()
    {
        return $this->belongsTo(ListReferences::class, 'semester_type_id');
    }

    public function subjects()
    {
        return $this->hasMany(SchoolCampusCourseCurriculumSubjects::class, 'curriculum_id');
    }

    public function course()
    {
        return $this->belongsTo(SchoolCampusCourses::class, 'campus_course_id');
    }

    public function replication()
    {
        return $this->hasOne(CurriculumReplication::class, 'curriculum_id');
    }
}
