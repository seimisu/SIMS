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
        if (!Schema::hasTable('scholars')) {
            return;
        }

        Schema::table('scholars', function (Blueprint $table) {
            if (!Schema::hasColumn('scholars', 'academic_status')) {
                $table->string('academic_status')->default('Ongoing')->after('status_id');
            }
        });

        DB::table('scholars')
            ->whereNull('academic_status')
            ->orWhere('academic_status', '')
            ->update(['academic_status' => 'Ongoing']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('scholars') || !Schema::hasColumn('scholars', 'academic_status')) {
            return;
        }

        Schema::table('scholars', function (Blueprint $table) {
            $table->dropColumn('academic_status');
        });
    }
};
