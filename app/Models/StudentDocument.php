<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    public $timestamps = false;

    protected $connection = 'scholars';

    protected $table = 'scholar_documents';

    public function getTermAttribute()
    {
        return $this->term_record_id;
    }
}
