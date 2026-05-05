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
        Schema::create('scholar_upload_temps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->nullable()->constrained('scholar_uploaded_files')->nullOnDelete();
            $table->string('spas_no')->unique();
            $table->string('status')->nullable();
            $table->string('standing')->nullable();
            $table->string('scholarship_type')->nullable();
            $table->string('scholarship_subprogram')->nullable();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('mname')->nullable();
            $table->string('suffix')->nullable();
            $table->enum('sex', ['M', 'F'])->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('contact_no')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('birthplace')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('address')->nullable();
            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('year_awarded')->nullable();
            $table->string('program')->nullable();
            $table->string('course')->nullable();
            $table->string('school')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholar_upload_temps');
    }
};
