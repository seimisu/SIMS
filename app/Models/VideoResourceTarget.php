<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoResourceTarget extends Model
{
    protected $fillable = [
        'video_resource_id',
        'target_type',
        'target_id',
    ];

    public function videoResource()
    {
        return $this->belongsTo(VideoResource::class);
    }
}
