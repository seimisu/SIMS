<?php

namespace Database\Seeders;

use App\Models\ListPermission;
use App\Models\ListPermissionRoute;
use App\Models\ListRole;
use App\Support\SystemPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ListPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = collect(SystemPermissions::permissionDefinitions());

        $permissions->each(function (array $definition, string $name) {
            ListPermission::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $definition['label'],
                    'group_name' => $definition['group'],
                    'description' => $definition['description'] ?? null,
                    'is_active' => true,
                ]
            );
        });

        collect(SystemPermissions::ROUTE_PERMISSIONS)->each(function (string $permissionName, string $routeName) {
            $permission = ListPermission::firstWhere('name', $permissionName);

            if (! $permission) {
                return;
            }

            ListPermissionRoute::updateOrCreate(
                ['route_name' => $routeName],
                ['permission_id' => $permission->id]
            );
        });

        ListRole::with('permissions')->get()->each(function (ListRole $role) {
            $roleName = Str::lower($role->name);
            $permissionNames = SystemPermissions::ROLE_PERMISSIONS[$roleName] ?? [];

            if (in_array('*', $permissionNames, true)) {
                $role->permissions()->sync(ListPermission::pluck('id')->all());

                return;
            }

            $permissionIds = ListPermission::whereIn('name', $permissionNames)->pluck('id')->all();
            $role->permissions()->sync($permissionIds);
        });
    }
}
