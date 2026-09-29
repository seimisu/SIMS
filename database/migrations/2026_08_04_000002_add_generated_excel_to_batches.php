<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('generated_excel_path')->nullable()->after('status');
            $table->string('generated_excel_name')->nullable()->after('generated_excel_path');
            $table->timestamp('generated_excel_at')->nullable()->after('generated_excel_name');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn([
                'generated_excel_path',
                'generated_excel_name',
                'generated_excel_at',
            ]);
        });
    }
};
