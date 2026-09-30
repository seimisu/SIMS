<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_statuses')
            ->where('type', 'progress')
            ->update([
                'type' => 'scholar',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('list_statuses')
            ->where('type', 'scholar')
            ->update([
                'type' => 'progress',
                'updated_at' => now(),
            ]);
    }
};
