<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')
            ->whereIn('slug', ['stipend-management', 'stipends'])
            ->update([
                'label' => 'Financial Assistance',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->where('slug', 'places')
            ->update([
                'label' => 'Locations',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('list_routes')
            ->whereIn('slug', ['stipend-management', 'stipends'])
            ->update([
                'label' => 'Stipend Management',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->where('slug', 'places')
            ->update([
                'label' => 'Places',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }
};
