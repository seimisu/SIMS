<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLogs extends Model
{
    public $fillable = [
        'user_id',
        'previous',
        'changes',
        'created_by',
        'request_type',
    ];

    protected $casts = [
        'previous' => 'array',
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
