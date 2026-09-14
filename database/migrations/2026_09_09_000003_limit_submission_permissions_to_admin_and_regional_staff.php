<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $permissionNames = [
        'profile-requests.view',
        'profile-requests.approve',
        'profile-requests.reject',
        'landbank-requests.view',
        'landbank-requests.approve',
        'landbank-requests.reject',
        'grade-submissions.view',
        'grade-submissions.approve',
        'grade-submissions.reject',
    ];

    private array $routeSlugs = [
        'scholar-submission-management',
        'scholar-submissions',
        'scholar-profile-requests',
        'scholar-landbank-requests',
    ];

    public function up(): void
    {
        collect($this->permissionNames)->each(fn (string $name) => $this->upsertPermission($name));

        $permissionIds = DB::table('list_permissions')
            ->whereIn('name', $this->permissionNames)
            ->pluck('id');

        $allowedRoles = DB::table('list_roles')
            ->whereIn(DB::raw('LOWER(name)'), ['administrator', 'regional staff'])
            ->where('is_active', true)
            ->get(['id', 'name']);

        $allowedRoleIds = $allowedRoles->pluck('id');

        DB::table('list_role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->when($allowedRoleIds->isNotEmpty(), fn ($query) => $query->whereNotIn('role_id', $allowedRoleIds))
            ->delete();

        $allowedRoleIds->each(function ($roleId) use ($permissionIds) {
            $permissionIds->each(function ($permissionId) use ($roleId) {
                DB::table('list_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });
        });

        if ($allowedRoles->isNotEmpty()) {
            DB::table('list_routes')
                ->whereIn('slug', $this->routeSlugs)
                ->update([
                    'roles' => $allowedRoles
                        ->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])
                        ->values()
                        ->toJson(),
                    'updated_by' => 'System',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank. Re-opening access to other roles should be an explicit decision.
    }

    private function upsertPermission(string $name): void
    {
        $group = Str::before($name, '.');
        $action = Str::of(Str::after($name, '.'))->replace('.', ' ')->headline()->toString();

        DB::table('list_permissions')->updateOrInsert(
            ['name' => $name],
            [
                'label' => Str::of($group)->headline().' - '.$action,
                'group_name' => $group,
                'description' => "Allows {$action} actions in the ".Str::of($group)->headline().' module.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
