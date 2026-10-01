<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_permissions')->updateOrInsert(
            ['name' => 'payroll.verify'],
            [
                'label' => 'Payroll - Verify',
                'group_name' => 'payroll',
                'description' => 'Allows scholarship staff to forward reviewed payroll to the scholarship coordinator.',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $staffRoleIds = DB::table('list_roles')
            ->whereRaw('LOWER(name) = ?', ['scholarship staff'])
            ->pluck('id');
        $verifyPermissionId = DB::table('list_permissions')->where('name', 'payroll.verify')->value('id');
        $approvePermissionId = DB::table('list_permissions')->where('name', 'payroll.approve')->value('id');

        foreach ($staffRoleIds as $roleId) {
            if ($verifyPermissionId) {
                DB::table('list_role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $verifyPermissionId,
                ]);
            }

            if ($approvePermissionId) {
                DB::table('list_role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $approvePermissionId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        $staffRoleIds = DB::table('list_roles')
            ->whereRaw('LOWER(name) = ?', ['scholarship staff'])
            ->pluck('id');
        $verifyPermissionId = DB::table('list_permissions')->where('name', 'payroll.verify')->value('id');
        $approvePermissionId = DB::table('list_permissions')->where('name', 'payroll.approve')->value('id');

        foreach ($staffRoleIds as $roleId) {
            if ($verifyPermissionId) {
                DB::table('list_role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $verifyPermissionId)
                    ->delete();
            }

            if ($approvePermissionId) {
                DB::table('list_role_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $approvePermissionId,
                ]);
            }
        }

        DB::table('list_permissions')
            ->where('name', 'payroll.verify')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
};
