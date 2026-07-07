<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class scholarLandbank extends Model
{
    protected $fillable = [
        'scholar_id',
        'account_number',
        'account_name',
        'uploaded_type',
        'uploaded_file',
        'created_by',
        'updated_by',
    ];

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id', 'id');
    }
}
