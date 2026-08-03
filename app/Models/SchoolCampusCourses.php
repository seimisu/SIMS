<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolCampusCourses extends Model
{
    protected $fillable = [
        'course_id',
        'campus_id',
        'years',
        'created_by',
        'updated_by',
        'is_active',
        'is_delete',
    ];

    protected $appends = ['course_array'];

    public function scholarCourse()
    {
        return $this->hasMany(ScholarSchoolInfos::class, 'campus_course_id', 'id');
    }

    public function campus()
    {
        return $this->belongsTo(SchoolCampuses::class, 'campus_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo(ListCourse::class, 'course_id', 'id');
    }

    public function subjects()
    {
        return $this->hasMany(SchoolCampusCourseSubjects::class, 'course_id', 'id');
    }

    public function curriculum()
    {
        return $this->hasMany(SchoolCampusCourseCurriculums::class, 'campus_course_id');
    }

    public function getCourseArrayAttribute()
    {
        return $this->course ? [
            'id' => $this->course->id,
            'name' => $this->course->name,
        ] : null;
    }
}
