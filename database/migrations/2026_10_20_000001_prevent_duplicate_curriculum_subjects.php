<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Lock writes until the check and index creation finish in this migration's transaction.
        DB::statement('LOCK TABLE school_campus_course_curriculum_subjects IN SHARE MODE');

        $duplicates = DB::selectOne(<<<'SQL'
            SELECT COUNT(*) AS total FROM (
                SELECT 1
                FROM school_campus_course_curriculum_subjects
                WHERE is_delete = false
                GROUP BY curriculum_id, COALESCE(semester_id, -1),
                    COALESCE(year::text, ''), LOWER(BTRIM(subject_code))
                HAVING COUNT(*) > 1
            ) duplicate_groups
        SQL);

        if ((int) $duplicates->total > 0) {
            throw new RuntimeException(
                "Cannot add subject uniqueness: {$duplicates->total} duplicate groups require review. "
                .'Preserve scholar grade links and combine applicable specializations before retrying.'
            );
        }

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX curriculum_subjects_active_code_unique
            ON school_campus_course_curriculum_subjects (
                curriculum_id,
                (COALESCE(semester_id, -1)),
                (COALESCE(year::text, '')),
                (LOWER(BTRIM(subject_code)))
            )
            WHERE is_delete = false
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS curriculum_subjects_active_code_unique');
    }
};
