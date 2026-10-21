<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recipient_allowances')) {
            return;
        }

        Schema::table('recipient_allowances', function (Blueprint $table) {
            if (! Schema::hasColumn('recipient_allowances', 'deposit_status')) {
                $table->string('deposit_status')->default('pending')->index();
            }

            if (! Schema::hasColumn('recipient_allowances', 'deposited_by')) {
                $table->foreignId('deposited_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('recipient_allowances', 'deposited_at')) {
                $table->timestamp('deposited_at')->nullable();
            }

            if (! Schema::hasColumn('recipient_allowances', 'deposit_remarks')) {
                $table->text('deposit_remarks')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('recipient_allowances')) {
            return;
        }

        Schema::table('recipient_allowances', function (Blueprint $table) {
            if (Schema::hasColumn('recipient_allowances', 'deposited_by')) {
                $table->dropConstrainedForeignId('deposited_by');
            }

            if (Schema::hasColumn('recipient_allowances', 'deposit_status')) {
                $table->dropIndex(['deposit_status']);
                $table->dropColumn('deposit_status');
            }

            foreach (['deposited_at', 'deposit_remarks'] as $column) {
                if (Schema::hasColumn('recipient_allowances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
