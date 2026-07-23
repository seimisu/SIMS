<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('batch_recipients', 'moved_from_batch_id')) {
                $table->foreignId('moved_from_batch_id')
                    ->nullable()
                    ->after('marked_for_removal_at')
                    ->constrained('batches')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('batch_recipients', 'moved_from_batch_name')) {
                $table->string('moved_from_batch_name')->nullable()->after('moved_from_batch_id');
            }

            if (!Schema::hasColumn('batch_recipients', 'moved_from_reason')) {
                $table->text('moved_from_reason')->nullable()->after('moved_from_batch_name');
            }

            if (!Schema::hasColumn('batch_recipients', 'moved_from_marked_by')) {
                $table->string('moved_from_marked_by')->nullable()->after('moved_from_reason');
            }

            if (!Schema::hasColumn('batch_recipients', 'moved_from_marked_at')) {
                $table->timestamp('moved_from_marked_at')->nullable()->after('moved_from_marked_by');
            }

            if (!Schema::hasColumn('batch_recipients', 'moved_notice_cleared_at')) {
                $table->timestamp('moved_notice_cleared_at')->nullable()->after('moved_from_marked_at');
            }
        });

        Schema::table('batch_recipients', function (Blueprint $table) {
            $table->index(
                ['batch_id', 'moved_from_batch_id', 'moved_notice_cleared_at'],
                'idx_batch_recipients_move_notice'
            );
        });
    }

    public function down(): void
    {
        Schema::table('batch_recipients', function (Blueprint $table) {
            $table->dropIndex('idx_batch_recipients_move_notice');

            if (Schema::hasColumn('batch_recipients', 'moved_from_batch_id')) {
                $table->dropForeign(['moved_from_batch_id']);
            }

            foreach ([
                'moved_notice_cleared_at',
                'moved_from_marked_at',
                'moved_from_marked_by',
                'moved_from_reason',
                'moved_from_batch_name',
                'moved_from_batch_id',
            ] as $column) {
                if (Schema::hasColumn('batch_recipients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
