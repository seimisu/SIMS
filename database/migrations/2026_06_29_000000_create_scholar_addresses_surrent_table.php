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
        Schema::create('scholar_addresses_current', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholar_id')->constrained('scholars')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('barangay_custom')->nullable();
            $table->string('barangay_code')->nullable();
            $table->foreign('barangay_code')->references('code')->on('location_barangays')->onDelete('set null');
            $table->string('municipality_code')->nullable();
            $table->foreign('municipality_code')->references('code')->on('location_cities')->onDelete('set null');
            $table->string('province_code')->nullable();
            $table->foreign('province_code')->references('code')->on('location_provinces')->onDelete('set null');
            $table->string('region_code')->nullable();
            $table->foreign('region_code')->references('code')->on('location_regions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholar_addresses_current');
    }
};
