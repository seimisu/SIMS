<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'video-resources'],
            [
                'label' => 'Video Library',
                'roles' => json_encode([
                    [
                        'id' => 1,
                        'name' => 'Administrator',
                    ],
                ]),
                'main_id' => 5,
                'route' => '/video-resources',
                'component' => 'Web/videoResourcePage',
                'icon' => 'IconVideo',
                'order_no' => 7,
                'is_submenu' => true,
                'is_active' => true,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $permissions = [
            'video-resources.view' => [
                'label' => 'Video Library - View',
                'group_name' => 'video-resources',
                'description' => 'Allows View actions in the Video Library module.',
            ],
            'video-resources.manage' => [
                'label' => 'Video Library - Manage',
                'group_name' => 'video-resources',
                'description' => 'Allows Manage actions in the Video Library module.',
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

        foreach ([
            'video-resources.store',
            'video-resources.update',
            'video-resources.destroy',
        ] as $routeName) {
            $permissionId = DB::table('list_permissions')
                ->where('name', 'video-resources.manage')
                ->value('id');

            if ($permissionId) {
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
            ->whereIn('name', ['video-resources.view', 'video-resources.manage'])
            ->pluck('id');

        DB::table('list_routes')->where('slug', 'video-resources')->delete();
        DB::table('list_permission_routes')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('list_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('list_permissions')->whereIn('id', $permissionIds)->delete();
    }
};
