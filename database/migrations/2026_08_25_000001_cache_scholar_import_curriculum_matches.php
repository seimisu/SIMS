<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->unsignedBigInteger('matched_curriculum_id')->nullable()->after('matched_course_campus');
            $table->string('matched_curriculum_name')->nullable()->after('matched_curriculum_id');
        });
    }

    public function down(): void
    {
        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->dropColumn([
                'matched_curriculum_id',
                'matched_curriculum_name',
            ]);
        });
    }
};
