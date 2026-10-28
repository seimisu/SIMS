<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarProfiles extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fname',
        'lname',
        'mname',
        'suffix',
        'photo',
        'contact_no',
        'birthdate',
        'birthplace',
        'email',
        'sex',
        'religion',
        'civil_status'
    ];


    public function getPhotoAttribute($value)
    {
        if (is_string($value) && str_starts_with($value, 'profile-photos/')) {
            return route('scholar-photos.show', ['scholar' => $this->scholar_id]);
        }

        return $value;
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id');
    }
}
