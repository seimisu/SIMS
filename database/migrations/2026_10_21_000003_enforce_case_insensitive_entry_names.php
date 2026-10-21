<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    private const INDEXES = [
        'roles' => ['list_roles', 'LOWER(TRIM(name))', 'name IS NOT NULL'],
        'role_slugs' => ['list_roles', 'LOWER(TRIM(slug))', 'slug IS NOT NULL'],
        'courses' => ['list_courses', 'LOWER(TRIM(name))', 'is_delete = false AND name IS NOT NULL'],
        'programs' => ['list_programs', 'LOWER(TRIM(name)), COALESCE(program_id, -1), COALESCE(type_id, -1)', 'is_delete = false AND name IS NOT NULL'],
        'references' => ['list_references', "LOWER(TRIM(name)), LOWER(TRIM(COALESCE(classification, ''))), LOWER(TRIM(COALESCE(type, '')))", 'is_delete = false AND name IS NOT NULL'],
        'statuses' => ['list_statuses', "LOWER(TRIM(name)), LOWER(TRIM(COALESCE(type, '')))", 'is_delete = false AND name IS NOT NULL'],
        'document_categories' => ['document_categories', 'LOWER(TRIM(name))', 'name IS NOT NULL'],
        'navigation' => ['list_routes', 'LOWER(TRIM(label)), COALESCE(CASE WHEN is_submenu THEN main_id ELSE NULL END, -1)', 'is_delete = false AND label IS NOT NULL'],
        'navigation_slugs' => ['list_routes', 'LOWER(TRIM(slug))', 'is_delete = false AND slug IS NOT NULL'],
    ];

    public function up(): void
    {
        $db = DB::connection($this->getConnection());
        $tables = array_unique(array_column(self::INDEXES, 0));
        $db->statement('LOCK TABLE '.implode(', ', $tables).' IN SHARE ROW EXCLUSIVE MODE');
        foreach (self::INDEXES as $area => [$table, $expressions, $filter]) {
            $duplicates = $db->selectOne("SELECT COUNT(*) AS total FROM (SELECT 1 FROM {$table} WHERE {$filter} GROUP BY {$expressions} HAVING COUNT(*) > 1) duplicates");
            if ((int) $duplicates->total > 0) {
                throw new RuntimeException("Cannot enforce {$area} uniqueness: {$duplicates->total} duplicate groups require review. No entries were renamed, deleted, or merged.");
            }
        }
        foreach (self::INDEXES as $area => [$table, $expressions, $filter]) {
            $db->statement("CREATE UNIQUE INDEX entry_{$area}_ci_unique ON {$table} ({$expressions}) WHERE {$filter}");
        }
        // Replace the old case-sensitive course rule; deleted courses retain their history.
        $db->statement('ALTER TABLE list_courses DROP CONSTRAINT IF EXISTS list_courses_name_unique');
    }

    public function down(): void
    {
        DB::connection($this->getConnection())->statement('ALTER TABLE list_courses ADD CONSTRAINT list_courses_name_unique UNIQUE (name)');
        foreach (array_keys(self::INDEXES) as $area) {
            DB::connection($this->getConnection())->statement("DROP INDEX IF EXISTS entry_{$area}_ci_unique");
        }
    }
};
