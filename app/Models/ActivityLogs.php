<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLogs extends Model
{
    protected $fillable = [
        'scholar_id',
        'previous_data',
        'changes_data',
        'created_by',
        'request_type',
    ];

    protected $casts = [
        'previous_data' => 'array',
        'changes_data' => 'array',
    ];

    protected $appends = ['previous_formatted', 'changes_formatted'];

    public function transformFormat($data)
    {
        $lookups = [
            'barangay_code' => [
                'model' => LocationBarangays::class,
                'search' => 'code',
                'display' => 'name',
                'new_key' => 'barangay',
            ],
            'municipality_code' => [
                'model' => LocationCity::class,
                'search' => 'code',
                'display' => 'name',
                'new_key' => 'municipality',
            ],
            'province_code' => [
                'model' => LocationProvinces::class,
                'search' => 'code',
                'display' => 'name',
                'new_key' => 'province',
            ],
            'region_code' => [
                'model' => LocationRegions::class,
                'search' => 'code',
                'display' => 'name',
                'new_key' => 'region',
            ],
            'campus_id' => [
                'model' => SchoolCampuses::class,
                'search' => 'id',
                'display' => 'generated_name',
                'new_key' => 'campus',
            ],
            'campus_course_id' => [
                'model' => ListCourse::class,
                'search' => 'id',
                'display' => 'name',
                'new_key' => 'course',
            ],
        ];

        foreach ($lookups as $field => $lookup) {
            if (! isset($data[$field])) {
                continue;
            }

            $data[$lookup['new_key']] = $lookup['model']::where(
                $lookup['search'],
                $data[$field]
            )->value($lookup['display']);

            unset($data[$field]);
        }

        return $data;

    }

    public function getPreviousFormattedAttribute()
    {
        return $this->transformFormat($this->previous_data);
    }

    public function getChangesFormattedAttribute()
    {
        return $this->transformFormat($this->changes_data);
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'id', 'scholar_id');
    }
}
