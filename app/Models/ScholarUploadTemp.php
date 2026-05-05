<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarUploadTemp extends Model
{
    protected $fillable = [
        'file_id',
        'spas_no',
        'status',
        'standing',
        'scholarship_type',
        'scholarship_subprogram',
        'fname',
        'lname',
        'mname',
        'suffix',
        'sex',
        'email',
        'contact_no',
        'birthdate',
        'birthplace',
        'civil_status',
        'address',
        'barangay',
        'municipality',
        'province',
        'region',
        'year_awarded',
        'program',
        'course',
        'school',
        'created_by',
        'updated_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'verified_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(ScholarUploadedFiles::class, 'file_id');
    }
}
