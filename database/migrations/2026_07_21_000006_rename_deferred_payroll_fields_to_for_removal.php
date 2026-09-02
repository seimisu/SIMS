<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('batch_recipients')) {
            Schema::table('batch_recipients', function (Blueprint $table) {
                if (Schema::hasColumn('batch_recipients', 'is_deferred_from_payroll') && !Schema::hasColumn('batch_recipients', 'is_for_removal_from_payroll')) {
                    $table->renameColumn('is_deferred_from_payroll', 'is_for_removal_from_payroll');
                }

                if (Schema::hasColumn('batch_recipients', 'deferred_by') && !Schema::hasColumn('batch_recipients', 'marked_for_removal_by')) {
                    $table->renameColumn('deferred_by', 'marked_for_removal_by');
                }

                if (Schema::hasColumn('batch_recipients', 'deferred_at') && !Schema::hasColumn('batch_recipients', 'marked_for_removal_at')) {
                    $table->renameColumn('deferred_at', 'marked_for_removal_at');
                }
            });

            DB::table('batch_recipients')
                ->where('status', 'deferred_from_payroll')
                ->update(['status' => 'for_removal_from_payroll']);
        }

        if (Schema::hasTable('payroll_batch_revision_recipients')) {
            Schema::table('payroll_batch_revision_recipients', function (Blueprint $table) {
                if (Schema::hasColumn('payroll_batch_revision_recipients', 'is_deferred') && !Schema::hasColumn('payroll_batch_revision_recipients', 'is_for_removal')) {
                    $table->renameColumn('is_deferred', 'is_for_removal');
                }

                if (Schema::hasColumn('payroll_batch_revision_recipients', 'deferred_by') && !Schema::hasColumn('payroll_batch_revision_recipients', 'marked_for_removal_by')) {
                    $table->renameColumn('deferred_by', 'marked_for_removal_by');
                }

                if (Schema::hasColumn('payroll_batch_revision_recipients', 'deferred_at') && !Schema::hasColumn('payroll_batch_revision_recipients', 'marked_for_removal_at')) {
                    $table->renameColumn('deferred_at', 'marked_for_removal_at');
                }
            });
        }

        if (Schema::hasTable('payroll_batch_activity_logs')) {
            DB::table('payroll_batch_activity_logs')
                ->where('action', 'scholar_deferred')
                ->update([
                    'action' => 'scholar_marked_for_removal',
                    'new_status' => 'for_removal_from_payroll',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('batch_recipients')) {
            DB::table('batch_recipients')
                ->where('status', 'for_removal_from_payroll')
                ->update(['status' => 'deferred_from_payroll']);

            Schema::table('batch_recipients', function (Blueprint $table) {
                if (Schema::hasColumn('batch_recipients', 'is_for_removal_from_payroll') && !Schema::hasColumn('batch_recipients', 'is_deferred_from_payroll')) {
                    $table->renameColumn('is_for_removal_from_payroll', 'is_deferred_from_payroll');
                }

                if (Schema::hasColumn('batch_recipients', 'marked_for_removal_by') && !Schema::hasColumn('batch_recipients', 'deferred_by')) {
                    $table->renameColumn('marked_for_removal_by', 'deferred_by');
                }

                if (Schema::hasColumn('batch_recipients', 'marked_for_removal_at') && !Schema::hasColumn('batch_recipients', 'deferred_at')) {
                    $table->renameColumn('marked_for_removal_at', 'deferred_at');
                }
            });
        }

        if (Schema::hasTable('payroll_batch_revision_recipients')) {
            Schema::table('payroll_batch_revision_recipients', function (Blueprint $table) {
                if (Schema::hasColumn('payroll_batch_revision_recipients', 'is_for_removal') && !Schema::hasColumn('payroll_batch_revision_recipients', 'is_deferred')) {
                    $table->renameColumn('is_for_removal', 'is_deferred');
                }

                if (Schema::hasColumn('payroll_batch_revision_recipients', 'marked_for_removal_by') && !Schema::hasColumn('payroll_batch_revision_recipients', 'deferred_by')) {
                    $table->renameColumn('marked_for_removal_by', 'deferred_by');
                }

                if (Schema::hasColumn('payroll_batch_revision_recipients', 'marked_for_removal_at') && !Schema::hasColumn('payroll_batch_revision_recipients', 'deferred_at')) {
                    $table->renameColumn('marked_for_removal_at', 'deferred_at');
                }
            });
        }

        if (Schema::hasTable('payroll_batch_activity_logs')) {
            DB::table('payroll_batch_activity_logs')
                ->where('action', 'scholar_marked_for_removal')
                ->update([
                    'action' => 'scholar_deferred',
                    'new_status' => 'deferred_from_payroll',
                ]);
        }
    }
};
