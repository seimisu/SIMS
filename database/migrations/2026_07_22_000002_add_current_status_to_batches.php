<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (! Schema::hasColumn('batches', 'status')) {
                $table->string('status')->default('draft')->after('is_lock');
            }
        });

        DB::table('batches')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'draft']);

        DB::statement("
            UPDATE batches
            SET status = COALESCE(latest.status, 'draft')
            FROM (
                SELECT DISTINCT ON (batch_id) batch_id, status
                FROM batches_logs
                WHERE batch_id IS NOT NULL
                ORDER BY batch_id, created_at DESC, id DESC
            ) latest
            WHERE latest.batch_id = batches.id
        ");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_lookup ON batches (region, school_year, term_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_name_lookup ON batches (region, school_year, academic_term, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_status_created ON batches (status, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_batches_status_created');
        DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_name_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_lookup');

        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
