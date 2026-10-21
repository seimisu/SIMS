<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        $db = DB::connection($this->getConnection());
        // Keep submissions from racing the duplicate check and index creation.
        $db->statement('LOCK TABLE scholar_term_records IN SHARE MODE');

        $duplicates = $db->selectOne(<<<'SQL'
            SELECT COUNT(*) AS total FROM (
                SELECT 1 FROM scholar_term_records
                WHERE scholar_id IS NOT NULL AND academic_year IS NOT NULL AND term_id IS NOT NULL
                GROUP BY scholar_id, TRIM(academic_year), term_id
                HAVING COUNT(*) > 1
            ) duplicate_groups
        SQL);

        if ((int) $duplicates->total > 0) {
            throw new RuntimeException(
                "Cannot enforce scholar academic period uniqueness: {$duplicates->total} duplicate groups require review. "
                .'Preserve grades, documents, and submission history when resolving them.'
            );
        }

        // Legacy records with unknown period details remain intact.
        $db->statement(<<<'SQL'
            CREATE UNIQUE INDEX scholar_academic_year_term_unique
            ON scholar_term_records (scholar_id, (TRIM(academic_year)), term_id)
            WHERE scholar_id IS NOT NULL AND academic_year IS NOT NULL AND term_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->getConnection())->statement('DROP INDEX IF EXISTS scholar_academic_year_term_unique');
    }
};
