<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (!Schema::hasColumn('batches', 'term_id')) {
                $table->foreignId('term_id')
                    ->nullable()
                    ->after('academic_term')
                    ->constrained('list_references')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('batches', 'level_id')) {
                $table->foreignId('level_id')
                    ->nullable()
                    ->after('term_id')
                    ->constrained('list_references')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'level_id')) {
                $table->dropForeign(['level_id']);
                $table->dropColumn('level_id');
            }

            if (Schema::hasColumn('batches', 'term_id')) {
                $table->dropForeign(['term_id']);
                $table->dropColumn('term_id');
            }
        });
    }
};
