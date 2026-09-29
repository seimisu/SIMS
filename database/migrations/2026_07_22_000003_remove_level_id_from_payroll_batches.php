<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_name_lookup');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_lookup ON batches (region, school_year, term_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_name_lookup ON batches (region, school_year, academic_term, status)');

        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'level_id')) {
                $table->dropForeign(['level_id']);
                $table->dropColumn('level_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (! Schema::hasColumn('batches', 'level_id')) {
                $table->foreignId('level_id')
                    ->nullable()
                    ->after('term_id')
                    ->constrained('list_references')
                    ->nullOnDelete();
            }
        });

        DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_name_lookup');

        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_lookup ON batches (region, school_year, term_id, level_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_name_lookup ON batches (region, school_year, academic_term, level_id, status)');
    }
};
