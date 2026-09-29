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
                    ->orWhere('slug', 'review');
            })
            ->update([
                'label' => 'scholar import review',
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
                    ->orWhere('slug', 'review');
            })
            ->update([
                'label' => 'pending scholar review',
                'updated_at' => now(),
            ]);
    }
};
