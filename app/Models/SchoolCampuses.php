<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolCampuses extends Model
{
    protected $fillable = [
        'school_id',
        'term_id',
        'name',
        'start_date',
        'end_date',
        'grading_id',
        'agency_id',
        'is_main',
        'created_by',
        'updated_by',
        'generated_name',
        'is_active',
        'is_delete',
    ];

    protected $hidden = [
        'agency',
        'grading',
        'term',
    ];

    protected $appends = ['term_array', 'grading_array', 'agency_array'];

    public function school()
    {
        return $this->belongsTo(Schools::class, 'school_id', 'id');
    }

    public function address()
    {
        return $this->hasOne(SchoolCampusAddresses::class, 'campus_id', 'id');
    }

    public function scholarCampus()
    {
        return $this->hasMany(ScholarSchoolInfos::class, 'campus_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(ListReferences::class, 'term_id', 'id');
    }

    public function courses()
    {
        return $this->hasMany(SchoolCampusCourses::class, 'campus_id', 'id');
    }

    public function grades()
    {
        return $this->hasMany(SchoolCampusGrades::class, 'campus_id', 'id');
    }

    public function grading()
    {
        return $this->belongsTo(ListReferences::class, 'grading_id', 'id');
    }

    public function agency()
    {
        return $this->belongsTo(ListAgencies::class, 'agency_id', 'id');
    }

    public function info()
    {
        return $this->hasOne(SchoolCampusInfo::class, 'campus_id', 'id');
    }

    public function semesters()
    {
        return $this->hasMany(SchoolCampusSemesters::class, 'campus_id', 'id');
    }

    public function coordinators()
    {
        return $this->hasMany(User::class, 'school_id', 'id');
    }

    // public function getFullnameCampusAttribute()
    // {
    //     return $this->school ? $this->school->name . ' - ' . $this->address->municipality->name : null;
    // }

    public function getTermArrayAttribute()
    {
        return $this->term ? [
            'id' => $this->term->id,
            'name' => $this->term->name,
        ] : null;
    }

    public function getGradingArrayAttribute()
    {
        return $this->grading ? [
            'id' => $this->grading->id,
            'name' => $this->grading->name,
        ] : null;
    }

    public function getAgencyArrayAttribute()
    {
        return $this->agency ? [
            'id' => $this->agency->id,
            'name' => $this->agency->name,
        ] : null;
    }
}
