<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('recipient_allowances')) {
            Schema::create('recipient_allowances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recipient_id')
                    ->constrained('batch_recipients')
                    ->cascadeOnDelete();
                $table->string('classification');
                $table->decimal('amount', 12, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        } else {
            Schema::table('recipient_allowances', function (Blueprint $table) {
                if (!Schema::hasColumn('recipient_allowances', 'recipient_id')) {
                    $table->foreignId('recipient_id')
                        ->nullable()
                        ->constrained('batch_recipients')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('recipient_allowances', 'classification')) {
                    $table->string('classification')->nullable();
                }

                if (!Schema::hasColumn('recipient_allowances', 'amount')) {
                    $table->decimal('amount', 12, 2)->default(0);
                }

                if (!Schema::hasColumn('recipient_allowances', 'remarks')) {
                    $table->text('remarks')->nullable();
                }

                if (!Schema::hasColumn('recipient_allowances', 'status')) {
                    $table->string('status')->default('pending');
                }
            });
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS recipient_allowances_recipient_id_classification_unique ON recipient_allowances (recipient_id, classification)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('recipient_allowances')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS recipient_allowances_recipient_id_classification_unique');
        Schema::dropIfExists('recipient_allowances');
    }
};
