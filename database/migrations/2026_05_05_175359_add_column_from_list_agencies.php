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
        if (!Schema::hasColumn('list_agencies', 'code')) {
            Schema::table('list_agencies', function (Blueprint $table) {
                $table->string('code')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('list_agencies', function (Blueprint $table) {
            if (Schema::hasColumn('list_agencies', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};