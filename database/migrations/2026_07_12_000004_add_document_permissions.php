<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'documents.view' => [
                'label' => 'Documents - View',
                'group_name' => 'documents',
                'description' => 'Allows View actions in the Documents module.',
            ],
            'documents.manage' => [
                'label' => 'Documents - Manage',
                'group_name' => 'documents',
                'description' => 'Allows Manage actions in the Documents module.',
            ],
        ];

        foreach ($permissions as $name => $permission) {
            DB::table('list_permissions')->updateOrInsert(
                ['name' => $name],
                [
                    ...$permission,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $routePermissions = [
            'documents.store' => 'documents.manage',
            'documents.update' => 'documents.manage',
            'documents.destroy' => 'documents.manage',
            'document-categories.store' => 'documents.manage',
            'document-categories.update' => 'documents.manage',
            'document-categories.destroy' => 'documents.manage',
        ];

        foreach ($routePermissions as $routeName => $permissionName) {
            $permissionId = DB::table('list_permissions')->where('name', $permissionName)->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('list_permission_routes')->updateOrInsert(
                ['route_name' => $routeName],
                [
                    'permission_id' => $permissionId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $administratorId = DB::table('list_roles')
            ->whereRaw('LOWER(name) = ?', ['administrator'])
            ->value('id');

        if ($administratorId) {
            DB::table('list_permissions')
                ->whereIn('name', array_keys($permissions))
                ->pluck('id')
                ->each(function ($permissionId) use ($administratorId) {
                    DB::table('list_role_permissions')->updateOrInsert(
                        [
                            'role_id' => $administratorId,
                            'permission_id' => $permissionId,
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('list_permissions')
            ->whereIn('name', ['documents.view', 'documents.manage'])
            ->pluck('id');

        DB::table('list_permission_routes')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('list_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('list_permissions')->whereIn('id', $permissionIds)->delete();
    }
};
