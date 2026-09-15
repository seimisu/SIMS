<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeolocationFiles extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'created_by',
        'file_hash',
    ];
}
