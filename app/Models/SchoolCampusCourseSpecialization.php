<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolCampusCourseSpecialization extends Model
{
    protected $fillable = ['campus_course_id', 'name', 'code', 'starts_at_year', 'is_active', 'is_delete'];

    protected $casts = ['starts_at_year' => 'integer', 'is_active' => 'boolean', 'is_delete' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(SchoolCampusCourses::class, 'campus_course_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(
            SchoolCampusCourseCurriculumSubjects::class,
            'curriculum_subject_specializations',
            'specialization_id',
            'subject_id'
        )->withTimestamps();
    }
}
