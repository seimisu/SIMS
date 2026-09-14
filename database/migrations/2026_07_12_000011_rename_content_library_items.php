<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')
            ->where('slug', 'documents')
            ->update([
                'label' => 'Downloadables',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->where('slug', 'video-resources')
            ->update([
                'label' => 'Video Library',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'documents.view')
            ->update([
                'label' => 'Downloadables - View',
                'description' => 'Allows View actions in the Downloadables module.',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'documents.manage')
            ->update([
                'label' => 'Downloadables - Manage',
                'description' => 'Allows Manage actions in the Downloadables module.',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'video-resources.view')
            ->update([
                'label' => 'Video Library - View',
                'description' => 'Allows View actions in the Video Library module.',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'video-resources.manage')
            ->update([
                'label' => 'Video Library - Manage',
                'description' => 'Allows Manage actions in the Video Library module.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('list_routes')
            ->where('slug', 'documents')
            ->update([
                'label' => 'Documents',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->where('slug', 'video-resources')
            ->update([
                'label' => 'Video Resources',
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);
    }
};
