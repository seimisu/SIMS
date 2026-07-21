<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batch_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('batch_recipient_id')->nullable()->constrained('batch_recipients')->nullOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('scholars')->nullOnDelete();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('remarks')->nullable();
            $table->json('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'created_at']);
            $table->index(['batch_recipient_id', 'created_at']);
            $table->index(['scholar_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batch_activity_logs');
    }
};
