<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class requestHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scholar_id',
        'request_type',
        'previous',
        'changes',
        'created_at',
        'created_by',
        'request_no',
    ];

    protected $casts = [
        'previous' => 'array',
        'changes' => 'array',
    ];

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id', 'id');
    }
}
