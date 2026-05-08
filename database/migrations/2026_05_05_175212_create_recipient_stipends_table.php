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
        Schema::create('recipient_stipends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')
                ->nullable()
                ->constrained('batch_recipients')
                ->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('month')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipient_stipends');
    }
};