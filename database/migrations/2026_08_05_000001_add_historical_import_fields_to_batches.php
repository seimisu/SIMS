<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (! Schema::hasColumn('batches', 'source')) {
                $table->string('source')->default('system')->index();
            }

            if (! Schema::hasColumn('batches', 'is_historical')) {
                $table->boolean('is_historical')->default(false)->index();
            }

            if (! Schema::hasColumn('batches', 'imported_by')) {
                $table->string('imported_by')->nullable();
            }

            if (! Schema::hasColumn('batches', 'imported_at')) {
                $table->timestamp('imported_at')->nullable();
            }

            if (! Schema::hasColumn('batches', 'import_file_path')) {
                $table->string('import_file_path')->nullable();
            }

            if (! Schema::hasColumn('batches', 'import_file_name')) {
                $table->string('import_file_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            foreach ([
                'source',
                'is_historical',
                'imported_by',
                'imported_at',
                'import_file_path',
                'import_file_name',
            ] as $column) {
                if (Schema::hasColumn('batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
