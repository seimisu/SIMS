<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->unsignedBigInteger('matched_school_id')->nullable()->after('validation_errors');
            $table->string('matched_school_name')->nullable()->after('matched_school_id');
            $table->unsignedBigInteger('matched_campus_id')->nullable()->after('matched_school_name');
            $table->unsignedBigInteger('matched_course_id')->nullable()->after('matched_campus_id');
            $table->string('matched_course_name')->nullable()->after('matched_course_id');
            $table->string('matched_course_campus')->nullable()->after('matched_course_name');
            $table->json('matched_address')->nullable()->after('matched_course_campus');
        });
    }

    public function down(): void
    {
        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->dropColumn([
                'matched_school_id',
                'matched_school_name',
                'matched_campus_id',
                'matched_course_id',
                'matched_course_name',
                'matched_course_campus',
                'matched_address',
            ]);
        });
    }
};
