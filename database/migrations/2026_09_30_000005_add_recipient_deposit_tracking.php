<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipient_stipends', function (Blueprint $table) {
            $table->foreignId('credited_by')->nullable()->after('remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('credited_at')->nullable()->after('credited_by');
            $table->index(['status', 'month_no', 'credited_at']);
        });
    }

    public function down(): void
    {
        Schema::table('recipient_stipends', function (Blueprint $table) {
            $table->dropIndex(['status', 'month_no', 'credited_at']);
            $table->dropConstrainedForeignId('credited_by');
            $table->dropColumn('credited_at');
        });
    }
};
