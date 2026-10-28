<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(['counts_for_gwa', 'required_for_scholarship'])
            ->filter(fn (string $column) => Schema::hasColumn('school_campus_course_curriculum_subjects', $column))
            ->all();

        if (empty($columns)) {
            return;
        }

        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('school_campus_course_curriculum_subjects', 'counts_for_gwa')) {
                $table->boolean('counts_for_gwa')->default(true);
            }

            if (! Schema::hasColumn('school_campus_course_curriculum_subjects', 'required_for_scholarship')) {
                $table->boolean('required_for_scholarship')->default(true);
            }
        });
    }
};
