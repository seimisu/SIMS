<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_campus_course_curriculums', function (Blueprint $table) {
            $table->unsignedSmallInteger('elective_limit')->nullable()->after('years');
        });

        DB::statement('
            UPDATE school_campus_course_curriculums AS curriculum
            SET elective_limit = course.elective_limit
            FROM school_campus_courses AS course
            WHERE curriculum.campus_course_id = course.id
              AND course.elective_limit IS NOT NULL
        ');

        Schema::table('school_campus_course_specializations', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
        Schema::table('school_campus_courses', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
    }

    public function down(): void
    {
        Schema::table('school_campus_courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('elective_limit')->nullable()->after('years');
        });
        Schema::table('school_campus_course_specializations', function (Blueprint $table) {
            $table->unsignedSmallInteger('elective_limit')->nullable()->after('starts_at_year');
        });
        Schema::table('school_campus_course_curriculums', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
    }
};
