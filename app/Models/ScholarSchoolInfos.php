<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarSchoolInfos extends Model
{
    protected $fillable = [
        'campus_id',
        'campus_course_id',
        'award_year',
        'graduated_year',
        'curriculum_id',
        'school_year',

    ];

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id');
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

    public function termRecords()
    {
        return $this->hasMany(ScholarTerm::class, 'scholar_school_id');
    }
}
