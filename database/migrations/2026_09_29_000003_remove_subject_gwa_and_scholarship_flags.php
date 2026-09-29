<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            $table->dropColumn(['counts_for_gwa', 'required_for_scholarship']);
        });
    }

    public function down(): void
    {
        Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
            $table->boolean('counts_for_gwa')->default(true);
            $table->boolean('required_for_scholarship')->default(true);
        });
    }
};
