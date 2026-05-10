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
        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            $table->string('change_school')->nullable()->after('school');
            $table->string('change_course')->nullable()->after('change_school');
            $table->json('change_fulladdress')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholar_upload_temps', function (Blueprint $table) {
            //
        });
    }
};
