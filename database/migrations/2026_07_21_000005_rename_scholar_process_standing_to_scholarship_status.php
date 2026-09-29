<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('scholars')->hasTable('scholar_processes')) {
            return;
        }

        if (
            Schema::connection('scholars')->hasColumn('scholar_processes', 'standing')
            && !Schema::connection('scholars')->hasColumn('scholar_processes', 'scholarship_status')
        ) {
            DB::connection('scholars')->statement(
                'ALTER TABLE scholar_processes RENAME COLUMN standing TO scholarship_status'
            );
        }

        if (
            Schema::connection('scholars')->hasColumn('scholar_processes', 'standing')
            && Schema::connection('scholars')->hasColumn('scholar_processes', 'scholarship_status')
        ) {
            DB::connection('scholars')
                ->table('scholar_processes')
                ->whereNull('scholarship_status')
                ->update([
                    'scholarship_status' => DB::raw('standing'),
                ]);

            DB::connection('scholars')->statement(
                'ALTER TABLE scholar_processes DROP COLUMN standing'
            );
        }
    }

    public function down(): void
    {
        if (!Schema::connection('scholars')->hasTable('scholar_processes')) {
            return;
        }

        if (
            Schema::connection('scholars')->hasColumn('scholar_processes', 'scholarship_status')
            && !Schema::connection('scholars')->hasColumn('scholar_processes', 'standing')
        ) {
            DB::connection('scholars')->statement(
                'ALTER TABLE scholar_processes RENAME COLUMN scholarship_status TO standing'
            );
        }
    }
};
