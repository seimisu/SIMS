<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoResource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'thumbnail_path',
        'is_active',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function targets()
    {
        return $this->hasMany(VideoResourceTarget::class);
    }
}
