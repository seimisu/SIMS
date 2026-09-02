<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batch_monthly_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->unsignedTinyInteger('month_no');
            $table->string('status')->default('pending');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedInteger('recipient_count')->default(0);
            $table->foreignId('credited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('credited_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'month_no']);
            $table->index(['status', 'credited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batch_monthly_credits');
    }
};
