<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_amount',
        'max_amount',
        'is_variable',
        'frequency',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_variable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function recipientAllowances()
    {
        return $this->hasMany(RecipientAllowance::class);
    }
}
