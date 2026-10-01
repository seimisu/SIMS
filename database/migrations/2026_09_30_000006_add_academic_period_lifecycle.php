<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_campus_semesters', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('school_year');
            $table->timestamp('opened_at')->nullable()->after('status');
            $table->foreignId('opened_by')->nullable()->after('opened_at')->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->after('opened_by');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->unique(
                ['campus_id', 'semester_id', 'school_year'],
                'campus_semester_school_year_unique'
            );
        });

        Schema::table('scholar_term_records', function (Blueprint $table) {
            $table->foreignId('campus_semester_id')
                ->nullable()
                ->after('scholar_school_id')
                ->constrained('school_campus_semesters')
                ->restrictOnDelete();
            $table->unique(
                ['scholar_id', 'campus_semester_id'],
                'scholar_campus_semester_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('scholar_term_records', function (Blueprint $table) {
            $table->dropUnique('scholar_campus_semester_unique');
            $table->dropConstrainedForeignId('campus_semester_id');
        });

        Schema::table('school_campus_semesters', function (Blueprint $table) {
            $table->dropUnique('campus_semester_school_year_unique');
            $table->dropConstrainedForeignId('closed_by');
            $table->dropConstrainedForeignId('opened_by');
            $table->dropColumn(['status', 'opened_at', 'closed_at']);
        });
    }
};
