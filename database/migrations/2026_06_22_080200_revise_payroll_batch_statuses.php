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
        if (! Schema::hasTable('batches_logs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE batches_logs DROP CONSTRAINT IF EXISTS batches_logs_status_check');
            DB::statement('ALTER TABLE batches_logs ALTER COLUMN status DROP DEFAULT');
            DB::statement('ALTER TABLE batches_logs ALTER COLUMN status TYPE VARCHAR(255)');
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE batches_logs MODIFY status VARCHAR(255) NOT NULL DEFAULT 'draft'");
        }

        DB::table('batches_logs')->where('status', 'pending')->update(['status' => 'draft']);
        DB::table('batches_logs')->where('status', 'approved')->update(['status' => 'submitted_payroll']);
        DB::table('batches_logs')->where('status', 'rejected')->update(['status' => 'rejected_payroll']);
        DB::table('batches_logs')->where('status', 'validated')->update(['status' => 'approved_payroll']);

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE batches_logs ALTER COLUMN status SET DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('batches_logs')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        DB::table('batches_logs')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('batches_logs')->where('status', 'submitted_payroll')->update(['status' => 'approved']);
        DB::table('batches_logs')->where('status', 'rejected_payroll')->update(['status' => 'rejected']);
        DB::table('batches_logs')->where('status', 'approved_payroll')->update(['status' => 'validated']);

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE batches_logs ALTER COLUMN status SET DEFAULT 'pending'");
            DB::statement("
                ALTER TABLE batches_logs
                ADD CONSTRAINT batches_logs_status_check
                CHECK (status IN ('pending', 'approved', 'rejected', 'validated'))
            ");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE batches_logs MODIFY status ENUM('pending', 'approved', 'rejected', 'validated') NOT NULL DEFAULT 'pending'");
        }
    }
};
