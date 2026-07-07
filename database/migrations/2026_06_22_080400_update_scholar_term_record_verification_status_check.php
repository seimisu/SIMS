<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('scholar_term_records')) {
            return;
        }

        DB::statement('ALTER TABLE scholar_term_records DROP CONSTRAINT IF EXISTS scholar_term_records_verification_status_check');
        DB::statement("
            ALTER TABLE scholar_term_records
            ADD CONSTRAINT scholar_term_records_verification_status_check
            CHECK (
                verification_status IN (
                    'pending',
                    'submitted',
                    'approved',
                    'rejected',
                    'draft_payroll',
                    'submitted_payroll',
                    'rejected_payroll',
                    'approved_payroll'
                )
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('scholar_term_records')) {
            return;
        }

        DB::statement('ALTER TABLE scholar_term_records DROP CONSTRAINT IF EXISTS scholar_term_records_verification_status_check');
        DB::statement("
            ALTER TABLE scholar_term_records
            ADD CONSTRAINT scholar_term_records_verification_status_check
            CHECK (
                verification_status IN (
                    'pending',
                    'submitted',
                    'approved',
                    'rejected'
                )
            )
        ");
    }
};
