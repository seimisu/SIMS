<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batch_revision_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_revision_id')->constrained('payroll_batch_revisions')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('batch_recipient_id')->nullable()->constrained('batch_recipients')->nullOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('scholars')->nullOnDelete();
            $table->json('row_payload');
            $table->boolean('is_deferred')->default(false);
            $table->string('deferred_by')->nullable();
            $table->timestamp('deferred_at')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'scholar_id']);
            $table->index(['payroll_batch_revision_id', 'is_deferred']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batch_revision_recipients');
    }
};
