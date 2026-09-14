<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTarget extends Model
{
    protected $fillable = [
        'document_id',
        'target_type',
        'target_id',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
