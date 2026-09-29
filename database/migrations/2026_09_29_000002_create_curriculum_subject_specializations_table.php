<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_subject_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('school_campus_course_curriculum_subjects')->cascadeOnDelete();
            $table->foreignId('specialization_id')->constrained('school_campus_course_specializations')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['subject_id', 'specialization_id']);
        });

        DB::table('school_campus_course_curriculum_subjects')
            ->whereNotNull('specialization_id')
            ->orderBy('id')
            ->chunkById(500, function ($subjects) {
                DB::table('curriculum_subject_specializations')->insertOrIgnore(
                    $subjects->map(fn ($subject) => [
                        'subject_id' => $subject->id,
                        'specialization_id' => $subject->specialization_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_subject_specializations');
    }
};
