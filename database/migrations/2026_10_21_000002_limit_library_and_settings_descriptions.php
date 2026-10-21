<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    private const COLUMNS = [
        'documents' => 'description',
        'document_categories' => 'description',
        'video_resources' => 'description',
        'list_programs' => 'others',
        'list_roles' => 'description',
        'school_campus_course_curriculum_subjects' => 'name',
    ];

    public function up(): void
    {
        $db = DB::connection($this->getConnection());
        $db->statement('LOCK TABLE '.implode(', ', array_keys(self::COLUMNS)).' IN ACCESS EXCLUSIVE MODE');

        foreach (self::COLUMNS as $table => $column) {
            $count = $db->table($table)->whereRaw("CHAR_LENGTH({$column}) > 200")->count();
            if ($count > 0) {
                throw new RuntimeException("Cannot limit {$table}.{$column}: {$count} existing values exceed 200 characters. Review and shorten them before retrying; no text has been truncated.");
            }
        }

        foreach (self::COLUMNS as $table => $column) {
            $db->statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE VARCHAR(200)");
        }
    }

    public function down(): void
    {
        $db = DB::connection($this->getConnection());
        foreach (self::COLUMNS as $table => $column) {
            $type = in_array($table, ['list_programs', 'school_campus_course_curriculum_subjects'], true) ? 'VARCHAR(255)' : 'TEXT';
            $db->statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$type}");
        }
    }
};
