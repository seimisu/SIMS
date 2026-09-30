<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


class SchoolCampusSemesters extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'campus_id',
        'semester_id',
        'start_date',
        'end_date',
        'is_delete',
        'is_active',
        'submission_date',
        'school_year',
        'status',
        'opened_at',
        'opened_by',
        'closed_at',
        'closed_by',
    ];


    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'submission_date' => 'date:Y-m-d',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $hidden = [
        'is_delete',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['semester_array'];

    public function campus()
    {
        return $this->belongsTo(SchoolCampuses::class, 'campus_id');
    }

    public function semester()
    {
        return $this->belongsTo(ListReferences::class, 'semester_id');
    }

    public function termRecords()
    {
        return $this->hasMany(ScholarTerm::class, 'campus_semester_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where('is_active', true)
            ->where('is_delete', false);
    }

    public function getSemesterArrayAttribute()
    {
        return $this->semester ? $this->semester->only(['id', 'name']) : null;
    }

    // public function setStartDateAttribute($value)
    // {
    //     $this->attributes['start_date'] = Carbon::parse($value)->format('Y-m-d');
    // }

    // public function setEndDateAttribute($value)
    // {
    //     $this->attributes['end_date'] = Carbon::parse($value)->format('Y-m-d');
    // }
}
