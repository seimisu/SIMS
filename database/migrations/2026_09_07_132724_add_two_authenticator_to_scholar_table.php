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
        Schema::table('scholars', function (Blueprint $table) {
            $table->text('authenticator_secret')->nullable();
            $table->text('authenticator_recovery_codes')->nullable();
            $table->boolean('authenticator_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholars', function (Blueprint $table) {
            $table->dropColumn(['authenticator_secret', 'authenticator_recovery_codes', 'authenticator_enabled']);
        });
    }
};
