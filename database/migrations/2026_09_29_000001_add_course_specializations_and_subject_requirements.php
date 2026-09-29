<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_campus_course_specializations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_course_id')->constrained('school_campus_courses')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedTinyInteger('starts_at_year')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_delete')->default(false);
            $table->timestamps();
        });

        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            $table->foreignId('specialization_id')->nullable()->after('curriculum_id')
                ->constrained('school_campus_course_specializations')->nullOnDelete();
            $table->string('requirement_type')->default('required')->after('subject_class');
        });

        Schema::table('scholar_school_infos', function (Blueprint $table) {
            $table->foreignId('specialization_id')->nullable()
                ->constrained('school_campus_course_specializations')->nullOnDelete();
        });

        Schema::table('scholar_term_records', function (Blueprint $table) {
            $table->foreignId('specialization_id')->nullable()
                ->constrained('school_campus_course_specializations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scholar_term_records', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialization_id'));
        Schema::table('scholar_school_infos', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialization_id'));
        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialization_id');
            $table->dropColumn('requirement_type');
        });
        Schema::dropIfExists('school_campus_course_specializations');
    }
};
