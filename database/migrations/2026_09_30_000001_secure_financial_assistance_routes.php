<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_permissions')->updateOrInsert(
            ['name' => 'payroll.delete'],
            [
                'label' => 'Payroll - Delete',
                'group_name' => 'payroll',
                'description' => 'Allows administrator-only deletion actions in the Payroll module.',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $routePermissions = [
            'stipends' => 'payroll.view',
            'cashier.credits' => 'payroll.credits.view',
            'stipends.import-historical.preview' => 'payroll.update',
            'stipends.import-historical' => 'payroll.update',
            'stipends.payroll.update' => 'payroll.update',
            'stipends.recipients.mark-for-removal' => 'payroll.recipients.manage-removal',
            'stipends.recipients.cancel-removal' => 'payroll.recipients.manage-removal',
            'cashier.credits.update' => 'payroll.credits.update',
            'stipends.export' => 'payroll.export',
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
    }

    public function down(): void
    {
        DB::table('list_permission_routes')
            ->whereIn('route_name', [
                'stipends',
                'cashier.credits',
                'stipends.import-historical.preview',
                'stipends.import-historical',
                'stipends.destroy',
            ])
            ->delete();

        DB::table('list_permissions')
            ->where('name', 'payroll.delete')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }
};
