<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'payroll.update' => [
                'label' => 'Payroll - Update',
                'group_name' => 'payroll',
                'description' => 'Allows updating payroll amounts, allowances, and remarks.',
            ],
            'payroll.export' => [
                'label' => 'Payroll - Export',
                'group_name' => 'payroll',
                'description' => 'Allows exporting payroll files.',
            ],
            'payroll.submit' => [
                'label' => 'Payroll - Submit',
                'group_name' => 'payroll',
                'description' => 'Allows uploading and submitting signed payroll files for review.',
            ],
            'payroll.approve' => [
                'label' => 'Payroll - Approve',
                'group_name' => 'payroll',
                'description' => 'Allows approving submitted payroll batches.',
            ],
            'payroll.return' => [
                'label' => 'Payroll - Return',
                'group_name' => 'payroll',
                'description' => 'Allows returning submitted payroll batches with remarks.',
            ],
            'payroll.recipients.manage-removal' => [
                'label' => 'Payroll Recipients - Manage Removal',
                'group_name' => 'payroll',
                'description' => 'Allows marking or cancelling scholar removal during payroll review.',
            ],
        ];

        foreach ($permissions as $name => $values) {
            DB::table('list_permissions')->updateOrInsert(
                ['name' => $name],
                [
                    ...$values,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->copyRoleAssignments('payroll.edit', 'payroll.update');
        $this->copyRoleAssignments('payroll.view', 'payroll.export');
        $this->copyRoleAssignments('payroll.reject', 'payroll.return');
        $this->copyRoleAssignments('payroll.review', 'payroll.recipients.manage-removal');

        $routePermissions = [
            'stipends.payroll.update' => 'payroll.update',
            'stipends.recipients.mark-for-removal' => 'payroll.recipients.manage-removal',
            'stipends.recipients.cancel-removal' => 'payroll.recipients.manage-removal',
            'stipends.export' => 'payroll.export',
            'stipends.update' => 'payroll.view',
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

        DB::table('list_permission_routes')
            ->where('route_name', 'stipends.destroy')
            ->delete();

        DB::table('list_permissions')
            ->whereIn('name', ['payroll.create', 'payroll.edit', 'payroll.review', 'payroll.reject', 'payroll.delete'])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $legacyPermissions = [
            'payroll.edit' => [
                'label' => 'Payroll - Edit',
                'group_name' => 'payroll',
                'description' => 'Allows Edit actions in the Payroll module.',
            ],
            'payroll.review' => [
                'label' => 'Payroll - Review',
                'group_name' => 'payroll',
                'description' => 'Allows Review actions in the Payroll module.',
            ],
            'payroll.reject' => [
                'label' => 'Payroll - Reject',
                'group_name' => 'payroll',
                'description' => 'Allows Reject actions in the Payroll module.',
            ],
            'payroll.delete' => [
                'label' => 'Payroll - Delete',
                'group_name' => 'payroll',
                'description' => 'Allows Delete actions in the Payroll module.',
            ],
        ];

        foreach ($legacyPermissions as $name => $values) {
            DB::table('list_permissions')->updateOrInsert(
                ['name' => $name],
                [
                    ...$values,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->copyRoleAssignments('payroll.update', 'payroll.edit');
        $this->copyRoleAssignments('payroll.recipients.manage-removal', 'payroll.review');
        $this->copyRoleAssignments('payroll.return', 'payroll.reject');

        $routePermissions = [
            'stipends.payroll.update' => 'payroll.edit',
            'stipends.recipients.mark-for-removal' => 'payroll.review',
            'stipends.recipients.cancel-removal' => 'payroll.review',
            'stipends.export' => 'payroll.view',
            'stipends.update' => 'payroll.view',
            'stipends.destroy' => 'payroll.delete',
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

        DB::table('list_permissions')
            ->whereIn('name', ['payroll.update', 'payroll.export', 'payroll.return', 'payroll.recipients.manage-removal'])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    private function copyRoleAssignments(string $fromPermission, string $toPermission): void
    {
        $fromId = DB::table('list_permissions')->where('name', $fromPermission)->value('id');
        $toId = DB::table('list_permissions')->where('name', $toPermission)->value('id');

        if (! $fromId || ! $toId) {
            return;
        }

        DB::table('list_role_permissions')
            ->where('permission_id', $fromId)
            ->pluck('role_id')
            ->each(function ($roleId) use ($toId) {
                DB::table('list_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $toId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }
};
