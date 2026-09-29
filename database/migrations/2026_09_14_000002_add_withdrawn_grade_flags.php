<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_campus_grades', function (Blueprint $table) {
            if (! Schema::hasColumn('school_campus_grades', 'is_withdrawn')) {
                $table->boolean('is_withdrawn')->default(false);
            }
        });

        Schema::table('scholar_school_grades', function (Blueprint $table) {
            if (! Schema::hasColumn('scholar_school_grades', 'is_withdrawn')) {
                $table->boolean('is_withdrawn')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_campus_grades', function (Blueprint $table) {
            if (Schema::hasColumn('school_campus_grades', 'is_withdrawn')) {
                $table->dropColumn('is_withdrawn');
            }
        });

        Schema::table('scholar_school_grades', function (Blueprint $table) {
            if (Schema::hasColumn('scholar_school_grades', 'is_withdrawn')) {
                $table->dropColumn('is_withdrawn');
            }
        });
    }
};
