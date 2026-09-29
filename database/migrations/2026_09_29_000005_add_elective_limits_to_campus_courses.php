<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_campus_courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('elective_limit')->nullable()->after('years');
        });

        Schema::table('school_campus_course_specializations', function (Blueprint $table) {
            $table->unsignedSmallInteger('elective_limit')->nullable()->after('starts_at_year');
        });
    }

    public function down(): void
    {
        Schema::table('school_campus_course_specializations', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
        Schema::table('school_campus_courses', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
    }
};
