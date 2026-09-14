<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_permissions')->updateOrInsert(
            ['name' => 'scholars.update'],
            [
                'label' => 'Scholars - Update',
                'group_name' => 'scholars',
                'description' => 'Allows Update actions in the Scholars module.',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $permissionId = DB::table('list_permissions')
            ->where('name', 'scholars.update')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        foreach (['scholars.update', 'scholar.grade-update', 'scholar.grade-delete'] as $routeName) {
            DB::table('list_permission_routes')->updateOrInsert(
                ['route_name' => $routeName],
                [
                    'permission_id' => $permissionId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('list_permissions')
            ->where('name', 'scholars.update')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }
};
