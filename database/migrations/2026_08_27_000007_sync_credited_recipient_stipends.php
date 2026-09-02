<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('payroll_batch_monthly_credits') ||
            ! Schema::hasTable('batch_recipients') ||
            ! Schema::hasTable('recipient_stipends')
        ) {
            return;
        }

        DB::table('payroll_batch_monthly_credits')
            ->join('batches', 'batches.id', '=', 'payroll_batch_monthly_credits.batch_id')
            ->where('payroll_batch_monthly_credits.status', 'credited')
            ->where(function ($query) {
                $query->where('batches.is_historical', false)
                    ->orWhereNull('batches.is_historical');
            })
            ->where(function ($query) {
                $query->where('batches.source', 'system')
                    ->orWhereNull('batches.source');
            })
            ->select([
                'payroll_batch_monthly_credits.batch_id',
                'payroll_batch_monthly_credits.month_no',
            ])
            ->orderBy('payroll_batch_monthly_credits.batch_id')
            ->get()
            ->each(function ($credit) {
                $recipientIds = DB::table('batch_recipients')
                    ->where('batch_id', $credit->batch_id)
                    ->where(function ($query) {
                        $query->where('is_for_removal_from_payroll', false)
                            ->orWhereNull('is_for_removal_from_payroll');
                    })
                    ->where('status', '!=', 'for_removal_from_payroll')
                    ->pluck('id');

                if ($recipientIds->isEmpty()) {
                    return;
                }

                DB::table('recipient_stipends')
                    ->whereIn('recipient_id', $recipientIds)
                    ->where('month_no', $credit->month_no)
                    ->where('amount', '>', 0)
                    ->update([
                        'status' => 'credited',
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
