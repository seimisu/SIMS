<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholar_school_grades', function (Blueprint $table) {
            if (! Schema::hasColumn('scholar_school_grades', 'input_grade')) {
                $table->string('input_grade')->nullable();
            }

            if (! Schema::hasColumn('scholar_school_grades', 'is_incomplete')) {
                $table->boolean('is_incomplete')->default(false);
            }

            if (! Schema::hasColumn('scholar_school_grades', 'is_drop')) {
                $table->boolean('is_drop')->default(false);
            }
        });

        DB::table('scholar_school_grades')
            ->whereNotNull('grade_id')
            ->whereNull('input_grade')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                $grades = DB::table('school_campus_grades')
                    ->whereIn('id', $rows->pluck('grade_id')->filter()->unique()->values())
                    ->get()
                    ->keyBy('id');

                foreach ($rows as $row) {
                    $grade = $grades->get($row->grade_id);

                    if (! $grade) {
                        continue;
                    }

                    DB::table('scholar_school_grades')
                        ->where('id', $row->id)
                        ->update([
                            'input_grade' => $grade->grade,
                            'is_incomplete' => $grade->is_incomplete,
                            'is_drop' => $grade->is_drop,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('scholar_school_grades', function (Blueprint $table) {
            $columns = collect(['input_grade', 'is_incomplete', 'is_drop'])
                ->filter(fn ($column) => Schema::hasColumn('scholar_school_grades', $column))
                ->all();

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
