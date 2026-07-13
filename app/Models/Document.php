<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_category_id',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'is_active',
        'published_at',
        'uploaded_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function targets()
    {
        return $this->hasMany(DocumentTarget::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
