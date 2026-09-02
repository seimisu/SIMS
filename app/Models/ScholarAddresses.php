<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarAddresses extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'address',
        'barangay_code',
        'municipality_code',
        'province_code',
        'region_code',
    ];

    public function barangay()
    {
        return $this->belongsTo(LocationBarangays::class, 'barangay_code', 'code');
    }

    public function municipality()
    {
        return $this->belongsTo(LocationCity::class, 'municipality_code', 'code');
    }

    public function province()
    {
        return $this->belongsTo(LocationProvinces::class, 'province_code', 'code');
    }

    public function region()
    {
        return $this->belongsTo(LocationRegions::class, 'region_code', 'code');
    }

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id');
    }

    public function getBarangayArrayAttribute()
    {
        return $this->barangay ? [
            'code' => $this->barangay->code,
            'name' => $this->barangay->name,
        ] : null;
    }

    // Municipality
    public function getMunicipalityArrayAttribute()
    {
        return $this->municipality ? [
            'code' => $this->municipality->code,
            'name' => $this->municipality->name,
        ] : null;
    }

    // Province
    public function getProvinceArrayAttribute()
    {
        return $this->province ? [
            'code' => $this->province->code,
            'name' => $this->province->name,
        ] : null;
    }

    // Region
    public function getRegionArrayAttribute()
    {
        return $this->region ? [
            'code' => $this->region->code,
            'name' => $this->region->name,
        ] : null;
    }

    public function getFullAddressAttribute()
    {
        return [
            'id' => $this->barangay_code.'-'.$this->municipality_code.'-'.$this->province_code.'-'.$this->region_code,
            'name' => ($this->barangay ? $this->barangay->name.', ' : '').
                ($this->municipality ? $this->municipality->name.', ' : '').
                ($this->province ? $this->province->name.', ' : '').
                ($this->region ? $this->region->name : ''),
        ];
    }

    public function getFullAddressWithStreetAttribute()
    {
        return
         ($this->address ? $this->address.', ' : '').
        ($this->barangay ? $this->barangay->name.', ' : '').
                ($this->municipality ? $this->municipality->name.', ' : '').
                ($this->province ? $this->province->name.', ' : '').
                ($this->region ? $this->region->name : '');

    }
}
