<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholar_academic_history_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholar_id')->nullable()->constrained('scholars')->nullOnDelete();
            $table->string('spas_no')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('return_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scholar_academic_history_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('scholar_academic_history_submissions')->cascadeOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('school_campuses')->nullOnDelete();
            $table->foreignId('campus_course_id')->nullable()->constrained('school_campus_courses')->nullOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('school_campus_course_curriculums')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('list_references')->nullOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('list_references')->nullOnDelete();
            $table->string('academic_year')->nullable();
            $table->string('school_name')->nullable();
            $table->string('course_name')->nullable();
            $table->string('term_name')->nullable();
            $table->string('level_name')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scholar_academic_history_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('history_term_id')->constrained('scholar_academic_history_terms')->cascadeOnDelete();
            $table->foreignId('matched_subject_id')->nullable()->constrained('school_campus_course_curriculum_subjects')->nullOnDelete();
            $table->string('subject_name')->nullable();
            $table->string('subject_code')->nullable();
            $table->string('subject_class')->nullable();
            $table->decimal('unit', 8, 2)->nullable();
            $table->string('grade')->nullable();
            $table->boolean('is_failed')->default(false);
            $table->boolean('is_incomplete')->default(false);
            $table->boolean('is_dropped')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scholar_academic_history_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('scholar_academic_history_submissions')->cascadeOnDelete();
            $table->foreignId('history_term_id')->nullable()->constrained('scholar_academic_history_terms')->cascadeOnDelete();
            $table->string('file_name')->nullable();
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholar_academic_history_files');
        Schema::dropIfExists('scholar_academic_history_subjects');
        Schema::dropIfExists('scholar_academic_history_terms');
        Schema::dropIfExists('scholar_academic_history_submissions');
    }
};
