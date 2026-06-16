<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class requestHistory extends Model
{
    protected $fillable = [
        'scholar_id',
        'request_type',
        'previous',
        'changes',
        'created_at',
        'created_by',
    ];

    protected $casts = [
        'prevous',
        'changes',
    ];

    public function scholar()
    {
        return $this->belongsTo(Scholars::class, 'scholar_id', 'id');
    }
}
