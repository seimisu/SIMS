<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListCourse extends Model
{
    protected $fillable = [
        'name',
        'field',
        'abbreviation',
        'is_active',
        'is_delete',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['suggestion_array'];

    public function getSuggestionArrayAttribute()
    {
        return $this->field ? [
            'id' => $this->field,
            'name' => $this->field,
        ] : null;
    }
}
