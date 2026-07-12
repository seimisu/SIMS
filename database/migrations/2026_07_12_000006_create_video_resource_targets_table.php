<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_resource_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_resource_id')->constrained('video_resources')->cascadeOnDelete();
            $table->string('target_type');
            $table->string('target_id')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->unique(['video_resource_id', 'target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_resource_targets');
    }
};
