<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')
            ->where('slug', 'scholar-submission-management')
            ->update([
                'label' => 'Submissions',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('list_routes')
            ->where('slug', 'scholar-submission-management')
            ->update([
                'label' => 'Submissions',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }
};
