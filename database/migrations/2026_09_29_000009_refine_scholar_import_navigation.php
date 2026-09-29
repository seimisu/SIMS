<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')
            ->where(function ($query) {
                $query->where('route', '/scholar-review')
                    ->orWhere('route', 'scholar-review')
                    ->orWhere('component', 'Web/reviewPage')
                    ->orWhereIn('slug', ['review', 'scholar-import-review']);
            })
            ->update([
                'label' => 'Scholar Import',
                'icon' => 'IconUserPlus',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->where(function ($query) {
                $query->whereIn('slug', ['financial-assistance', 'stipend-management', 'stipends'])
                    ->orWhere('component', 'Web/stipendPage');
            })
            ->update([
                'icon' => 'IconWallet',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('list_routes')
            ->where(function ($query) {
                $query->where('route', '/scholar-review')
                    ->orWhere('route', 'scholar-review')
                    ->orWhere('component', 'Web/reviewPage')
                    ->orWhereIn('slug', ['review', 'scholar-import-review']);
            })
            ->update([
                'label' => 'scholar import review',
                'icon' => 'IconFileImport',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->where(function ($query) {
                $query->whereIn('slug', ['financial-assistance', 'stipend-management', 'stipends'])
                    ->orWhere('component', 'Web/stipendPage');
            })
            ->update([
                'icon' => 'IconHandCoins',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }
};
