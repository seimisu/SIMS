<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('allowance_types')) {
            Schema::create('allowance_types', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->decimal('default_amount', 12, 2)->nullable();
                $table->decimal('max_amount', 12, 2)->nullable();
                $table->boolean('is_variable')->default(false);
                $table->string('frequency')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $allowanceTypes = [
            [
                'code' => 'monthly_living',
                'name' => 'Monthly Living Allowance',
                'default_amount' => 8000,
                'max_amount' => null,
                'is_variable' => false,
                'frequency' => 'monthly',
                'description' => 'Monthly living stipend.',
            ],
            [
                'code' => 'tuition_school_fees',
                'name' => 'Tuition & School Fees',
                'default_amount' => null,
                'max_amount' => 40000,
                'is_variable' => true,
                'frequency' => 'academic_year',
                'description' => 'Subsidy for scholars enrolled in private universities or colleges.',
            ],
            [
                'code' => 'connectivity',
                'name' => 'Learning Materials & Connectivity Allowance',
                'default_amount' => 10000,
                'max_amount' => null,
                'is_variable' => false,
                'frequency' => 'academic_year',
                'description' => 'Annual learning materials and connectivity allowance.',
            ],
            [
                'code' => 'clothing',
                'name' => 'Clothing Allowance',
                'default_amount' => 1000,
                'max_amount' => null,
                'is_variable' => false,
                'frequency' => 'first_semester_first_year',
                'description' => 'Granted during the first semester of the first year only.',
            ],
            [
                'code' => 'transportation',
                'name' => 'Transportation Allowance',
                'default_amount' => null,
                'max_amount' => null,
                'is_variable' => true,
                'frequency' => 'academic_year',
                'description' => 'One-way economy-class roundtrip fare per academic year for scholars studying outside their home province.',
            ],
            [
                'code' => 'thesis',
                'name' => 'Thesis Allowance',
                'default_amount' => 10000,
                'max_amount' => null,
                'is_variable' => false,
                'frequency' => 'once',
                'description' => 'Thesis allowance for eligible scholars.',
            ],
            [
                'code' => 'graduation',
                'name' => 'Graduation Allowance',
                'default_amount' => 1000,
                'max_amount' => null,
                'is_variable' => false,
                'frequency' => 'once',
                'description' => 'Graduation allowance for graduating scholars.',
            ],
        ];

        foreach ($allowanceTypes as $allowanceType) {
            $existing = DB::table('allowance_types')->where('code', $allowanceType['code'])->exists();

            DB::table('allowance_types')->updateOrInsert(
                ['code' => $allowanceType['code']],
                array_merge($allowanceType, [
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $existing ? DB::raw('created_at') : $now,
                ])
            );
        }

        if (Schema::hasTable('recipient_allowances') && !Schema::hasColumn('recipient_allowances', 'allowance_type_id')) {
            Schema::table('recipient_allowances', function (Blueprint $table) {
                $table->foreignId('allowance_type_id')
                    ->nullable()
                    ->after('recipient_id')
                    ->constrained('allowance_types')
                    ->nullOnDelete();
            });
        }

        $codeAliases = [
            'connectivity' => 'connectivity',
            'learning_materials_connectivity' => 'connectivity',
            'clothing' => 'clothing',
        ];

        foreach ($codeAliases as $classification => $code) {
            $allowanceTypeId = DB::table('allowance_types')->where('code', $code)->value('id');

            if ($allowanceTypeId) {
                DB::table('recipient_allowances')
                    ->where('classification', $classification)
                    ->whereNull('allowance_type_id')
                    ->update(['allowance_type_id' => $allowanceTypeId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recipient_allowances') && Schema::hasColumn('recipient_allowances', 'allowance_type_id')) {
            Schema::table('recipient_allowances', function (Blueprint $table) {
                $table->dropConstrainedForeignId('allowance_type_id');
            });
        }

        Schema::dropIfExists('allowance_types');
    }
};
