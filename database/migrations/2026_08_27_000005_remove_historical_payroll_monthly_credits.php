<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_batch_monthly_credits') || ! Schema::hasTable('batches')) {
            return;
        }

        DB::table('payroll_batch_monthly_credits')
            ->whereIn('batch_id', function ($query) {
                $query->select('id')
                    ->from('batches')
                    ->where(function ($query) {
                        $query->where('is_historical', true)
                            ->orWhere(function ($query) {
                                $query->whereNotNull('source')
                                    ->where('source', '!=', 'system');
                            });
                    });
            })
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
