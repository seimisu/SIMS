<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSubject extends Model
{
    protected $connection = 'scholars';
    protected $table = 'student_subjects';
    public $timestamps = false;
    protected $fillable = ['spas_no', 'term_record_id', 'status', 'requested_at', 'updated_at', 'updated_by', 'remarks'];

    public function termRecord()
    {
        return $this->belongsTo(ScholarTerm::class, 'term_record_id');
    }

}
