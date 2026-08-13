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
        Schema::create('list_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('others')->default('n/a');
            $table->bigInteger('program_id')->unsigned()->index();
            $table->foreign('program_id')->references('id')->on('list_references')->onDelete('cascade');
            $table->bigInteger('type_id')->unsigned()->index();
            $table->foreign('type_id')->references('id')->on('list_references')->onDelete('cascade');
            $table->boolean('is_sub')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_delete')->default(false);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_programs');
    }
};
