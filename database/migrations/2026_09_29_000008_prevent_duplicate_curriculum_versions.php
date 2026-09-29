<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE UNIQUE INDEX school_curriculums_active_version_unique
            ON school_campus_course_curriculums (campus_course_id, LOWER(BTRIM(years)))
            WHERE is_delete = false
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS school_curriculums_active_version_unique');
    }
};
