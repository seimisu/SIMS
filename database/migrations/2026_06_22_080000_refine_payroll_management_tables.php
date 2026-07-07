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
        if (Schema::hasTable('batch_recipients')) {
            try {
                Schema::table('batch_recipients', function (Blueprint $table) {
                    $table->dropUnique('batch_recipients_email_unique');
                });
            } catch (\Throwable $th) {
                // The index may not exist on databases created after the payroll cleanup.
            }

            Schema::table('batch_recipients', function (Blueprint $table) {
                if (!Schema::hasColumn('batch_recipients', 'batch_id')) {
                    $table->foreignId('batch_id')
                        ->nullable()
                        ->constrained('batches')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('batch_recipients', 'scholar_id')) {
                    $table->foreignId('scholar_id')
                        ->nullable()
                        ->constrained('scholars')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('batch_recipients', 'account_no')) {
                    $table->string('account_no')->nullable();
                }

                if (!Schema::hasColumn('batch_recipients', 'scholarship_status')) {
                    $table->string('scholarship_status')->nullable();
                }

                if (!Schema::hasColumn('batch_recipients', 'total_stipend')) {
                    $table->decimal('total_stipend', 12, 2)->default(0);
                }

                if (!Schema::hasColumn('batch_recipients', 'total_withheld')) {
                    $table->decimal('total_withheld', 12, 2)->default(0);
                }

                if (!Schema::hasColumn('batch_recipients', 'learning_materials_amount')) {
                    $table->decimal('learning_materials_amount', 12, 2)->default(0);
                }

                if (!Schema::hasColumn('batch_recipients', 'clothing_amount')) {
                    $table->decimal('clothing_amount', 12, 2)->default(0);
                }

                if (!Schema::hasColumn('batch_recipients', 'grand_total')) {
                    $table->decimal('grand_total', 12, 2)->default(0);
                }

                if (!Schema::hasColumn('batch_recipients', 'remarks')) {
                    $table->text('remarks')->nullable();
                }

                if (!Schema::hasColumn('batch_recipients', 'status')) {
                    $table->string('status')->default('pending');
                }
            });

            try {
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS batch_recipients_batch_id_scholar_id_unique ON batch_recipients (batch_id, scholar_id)');
            } catch (\Throwable $th) {
                // Existing duplicate rows should be cleaned before adding the unique index.
            }
        }

        if (Schema::hasTable('recipient_stipends')) {
            Schema::table('recipient_stipends', function (Blueprint $table) {
                if (!Schema::hasColumn('recipient_stipends', 'month_no')) {
                    $table->unsignedTinyInteger('month_no')->nullable();
                }

                if (!Schema::hasColumn('recipient_stipends', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('recipient_withhelds')) {
            Schema::table('recipient_withhelds', function (Blueprint $table) {
                if (!Schema::hasColumn('recipient_withhelds', 'month_no')) {
                    $table->unsignedTinyInteger('month_no')->nullable();
                }

                if (!Schema::hasColumn('recipient_withhelds', 'created_at')) {
                    $table->timestamps();
                }
            });
        }

        Schema::dropIfExists('recipient_allowances');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('recipient_allowances')) {
            Schema::create('recipient_allowances', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('recipient_withhelds')) {
            Schema::table('recipient_withhelds', function (Blueprint $table) {
                if (Schema::hasColumn('recipient_withhelds', 'month_no')) {
                    $table->dropColumn('month_no');
                }

                if (Schema::hasColumn('recipient_withhelds', 'created_at')) {
                    $table->dropTimestamps();
                }
            });
        }

        if (Schema::hasTable('recipient_stipends')) {
            Schema::table('recipient_stipends', function (Blueprint $table) {
                if (Schema::hasColumn('recipient_stipends', 'month_no')) {
                    $table->dropColumn('month_no');
                }

                if (Schema::hasColumn('recipient_stipends', 'remarks')) {
                    $table->dropColumn('remarks');
                }
            });
        }

        if (Schema::hasTable('batch_recipients')) {
            Schema::table('batch_recipients', function (Blueprint $table) {
                foreach ([
                    'account_no',
                    'scholarship_status',
                    'total_stipend',
                    'total_withheld',
                    'learning_materials_amount',
                    'clothing_amount',
                    'grand_total',
                    'remarks',
                    'status',
                ] as $column) {
                    if (Schema::hasColumn('batch_recipients', $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn('batch_recipients', 'scholar_id')) {
                    $table->dropForeign(['scholar_id']);
                    $table->dropColumn('scholar_id');
                }

                if (Schema::hasColumn('batch_recipients', 'batch_id')) {
                    $table->dropForeign(['batch_id']);
                    $table->dropColumn('batch_id');
                }
            });
        }
    }
};
