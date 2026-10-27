<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBarangays extends Model
{

    protected $fillable = [
        'name',
        'code',
        'old_name',
        'district',
        'municipality_code',
        'region_code',
        'is_active',
        'is_delete',
        'created_by',
        'updated_by'
    ];

    protected $appends = ['city_array'];



    public function cityCode()
    {
        return $this->belongsTo(LocationCity::class, 'municipality_code', 'code');
    }

    public function getCityArrayAttribute()
    {
        return $this->cityCode ? [
            'id' => $this->municipality_code,
            'name' => $this->cityCode->name,
        ] : null;
    }
}
