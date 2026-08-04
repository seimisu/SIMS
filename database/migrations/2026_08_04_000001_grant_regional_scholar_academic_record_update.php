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
                'description' => 'Allows updating scholar details and academic records.',
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

        DB::table('list_roles')
            ->whereIn(DB::raw('LOWER(name)'), ['regional staff', 'regional supervisor'])
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId) {
                DB::table('list_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('list_permissions')
            ->where('name', 'scholars.update')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('list_roles')
            ->whereIn(DB::raw('LOWER(name)'), ['regional staff', 'regional supervisor'])
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId) {
                DB::table('list_role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->delete();
            });
    }
};
