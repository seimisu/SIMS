<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class ScholarUploadedFiles extends Model
{
    protected $fillable = [
        'filename',
        'filepath',
        'region_office',
        'created_by',
        'validated_by',
        'validated_at',
        'created_at',
        'status',

    ];

    protected $hidden = [
        'id',
        'filepath',
    ];

    protected $appends = ['formatted_date', 'validated_formatted_date', 'hash_id', 'file_url'];

    public function getFormattedDateAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('M d, Y | h:i a')
            : null;
    }

    public function getFileUrlAttribute()
    {
        return $this->filepath
            ? asset('storage/'.$this->filepath)
            : null;
    }

    public function getValidatedFormattedDateAttribute()
    {
        return $this->validated_at
            ? Carbon::parse($this->validated_at)->format('M d, Y | h:i a')
            : null;
    }

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function temp()
    {
        return $this->hasMany(ScholarUploadTemp::class, 'file_id');
    }
}
