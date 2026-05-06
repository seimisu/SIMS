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
        Schema::create('recipient_withhelds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')
                ->nullable()
                ->constrained('batch_recipients')
                ->nullOnDelete();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipient_withhelds');
    }
};