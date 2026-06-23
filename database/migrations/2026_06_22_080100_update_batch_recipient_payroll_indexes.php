<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('batch_recipients')) {
            return;
        }

        try {
            Schema::table('batch_recipients', function (Blueprint $table) {
                $table->dropUnique('batch_recipients_email_unique');
            });
        } catch (\Throwable $th) {
            // The old email-only unique index may already be absent.
        }

        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS batch_recipients_batch_id_scholar_id_unique ON batch_recipients (batch_id, scholar_id)');
        } catch (\Throwable $th) {
            // Duplicate recipient rows must be cleaned before this index can be added.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('batch_recipients')) {
            return;
        }

        try {
            DB::statement('DROP INDEX IF EXISTS batch_recipients_batch_id_scholar_id_unique');
        } catch (\Throwable $th) {
            //
        }
    }
};
