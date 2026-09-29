<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('elective_group_id');
        });

        Schema::dropIfExists('curriculum_elective_groups');
    }

    public function down(): void
    {
        Schema::create('curriculum_elective_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('school_campus_course_curriculums')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('list_references')->cascadeOnDelete();
            $table->string('year');
            $table->string('name');
            $table->unsignedSmallInteger('minimum_required')->default(0);
            $table->unsignedSmallInteger('maximum_allowed')->nullable();
            $table->timestamps();
            $table->unique(['curriculum_id', 'semester_id', 'year', 'name'], 'curriculum_elective_groups_unique');
        });

        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            $table->foreignId('elective_group_id')->nullable()->after('specialization_id')
                ->constrained('curriculum_elective_groups')->nullOnDelete();
        });
    }
};
