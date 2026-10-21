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
            SET status = COALESCE((
                SELECT batches_logs.status
                FROM batches_logs
                WHERE batches_logs.batch_id = batches.id
                ORDER BY batches_logs.created_at DESC, batches_logs.id DESC
                LIMIT 1
            ), 'draft')
            WHERE EXISTS (
                SELECT 1
                FROM batches_logs
                WHERE batches_logs.batch_id = batches.id
            )
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
