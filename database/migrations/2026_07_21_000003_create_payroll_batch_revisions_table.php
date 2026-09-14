<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batch_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->json('recipients_snapshot');
            $table->json('totals_snapshot')->nullable();
            $table->string('payroll_file_path')->nullable();
            $table->string('payroll_file_name')->nullable();
            $table->string('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'revision_no']);
            $table->index(['batch_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batch_revisions');
    }
};
