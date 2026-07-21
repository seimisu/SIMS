<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_recipients', 'is_deferred_from_payroll')) {
                $table->boolean('is_deferred_from_payroll')->default(false);
            }

            if (!Schema::hasColumn('batch_recipients', 'deferred_by')) {
                $table->string('deferred_by')->nullable();
            }

            if (!Schema::hasColumn('batch_recipients', 'deferred_at')) {
                $table->timestamp('deferred_at')->nullable();
            }
        });

        DB::table('batch_recipients')
            ->where('status', 'deferred_from_payroll')
            ->update(['is_deferred_from_payroll' => true]);
    }

    public function down(): void
    {
        Schema::table('batch_recipients', function (Blueprint $table) {
            foreach (['deferred_at', 'deferred_by', 'is_deferred_from_payroll'] as $column) {
                if (Schema::hasColumn('batch_recipients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
