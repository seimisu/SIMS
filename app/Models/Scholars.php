<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scholars extends Model
{
    protected $fillable = [
        'spas_no',
        'status_id',
        'program_id',
        'type_id',
        'category_id',
        'is_active',
        'is_delete',
        'created_by',
        'updated_by',
        'activation_token',
        'activated_at',
        'award_year',
        'verified_by',
        'verified_at',
        'validate_status',
    ];

    public function requestHistory()
    {
        return $this->hasMany(requestHistory::class, 'scholar_id', 'id');
    }

    public function profileRequest()
    {
        return $this->hasMany(StudentProfileRequest::class, 'spas_no', 'spas_no');
    }

    public function landbankRequest()
    {
        return $this->hasMany(studentLandbankRequest::class, 'spas_no', 'spas_no');
    }

    public function status()
    {
        return $this->belongsTo(ListStatuses::class, 'status_id');
    }

    public function program()
    {
        return $this->belongsTo(ListPrograms::class, 'program_id');
    }

    public function mainProgram()
    {
        return $this->belongsTo(ListReferences::class, 'category_id');
    }

    public function landbank()
    {
        return $this->hasOne(scholarLandbank::class, 'scholar_id');
    }

    public function type()
    {
        return $this->belongsTo(ListReferences::class, 'type_id');
    }

    public function profile()
    {
        return $this->hasOne(ScholarProfiles::class, 'scholar_id');
    }

    public function address()
    {
        return $this->hasOne(ScholarAddresses::class, 'scholar_id', 'id');
    }

    public function parent()
    {
        return $this->hasOne(ScholarParentDetails::class, 'scholar_id');
    }

    public function schoolInfo()
    {
        return $this->hasMany(ScholarSchoolInfos::class, 'scholar_id');
    }

    public function termRecords()
    {
        return $this->hasMany(ScholarTerm::class, 'scholar_id');
    }

    public function getTypeArrayAttribute()
    {
        return $this->type_id ? $this->type->only('id', 'name') : null;
    }
}
