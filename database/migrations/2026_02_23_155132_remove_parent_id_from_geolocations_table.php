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
        Schema::table('geolocations', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['parent_id', 'type']);
            $table->dropColumn('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geolocations', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('geolocations')
                ->cascadeOnDelete();
            $table->index('parent_id');
            $table->index(['parent_id', 'type']);
        });
    }
};
