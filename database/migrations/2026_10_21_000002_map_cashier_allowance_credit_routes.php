<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $routePermissions = [
            'cashier.credits.allowances' => 'payroll.credits.view',
            'cashier.credits.allowances.update' => 'payroll.credits.update',
        ];

        foreach ($routePermissions as $routeName => $permissionName) {
            $permissionId = DB::table('list_permissions')
                ->where('name', $permissionName)
                ->value('id');

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
                'cashier.credits.allowances',
                'cashier.credits.allowances.update',
            ])
            ->delete();
    }
};
