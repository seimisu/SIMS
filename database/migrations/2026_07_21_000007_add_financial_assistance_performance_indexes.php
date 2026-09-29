<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_logs_batch_created ON batches_logs (batch_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batch_recipients_batch ON batch_recipients (batch_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batch_recipients_scholar ON batch_recipients (scholar_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_batch_recipients_batch_scholar ON batch_recipients (batch_id, scholar_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recipient_stipends_recipient_month ON recipient_stipends (recipient_id, month_no)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recipient_withhelds_recipient_month ON recipient_withhelds (recipient_id, month_no)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_recipient_allowances_recipient ON recipient_allowances (recipient_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payroll_activity_logs_batch_action_created ON payroll_batch_activity_logs (batch_id, action, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payroll_revisions_batch_revision ON payroll_batch_revisions (batch_id, revision_no DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_revision_recipients_revision ON payroll_batch_revision_recipients (payroll_batch_revision_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_batches_logs_batch_created');
        DB::statement('DROP INDEX IF EXISTS idx_batch_recipients_batch');
        DB::statement('DROP INDEX IF EXISTS idx_batch_recipients_scholar');
        DB::statement('DROP INDEX IF EXISTS idx_batch_recipients_batch_scholar');
        DB::statement('DROP INDEX IF EXISTS idx_recipient_stipends_recipient_month');
        DB::statement('DROP INDEX IF EXISTS idx_recipient_withhelds_recipient_month');
        DB::statement('DROP INDEX IF EXISTS idx_recipient_allowances_recipient');
        DB::statement('DROP INDEX IF EXISTS idx_payroll_activity_logs_batch_action_created');
        DB::statement('DROP INDEX IF EXISTS idx_payroll_revisions_batch_revision');
        DB::statement('DROP INDEX IF EXISTS idx_revision_recipients_revision');
    }
};
