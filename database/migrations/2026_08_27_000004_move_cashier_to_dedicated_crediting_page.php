<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cashierId = DB::table('list_roles')->where('slug', 'cashier')->value('id');

        if (! $cashierId) {
            return;
        }

        DB::table('list_permission_routes')->where('route_name', 'stipends.credits.update')->delete();
        $this->removeCashierFromStipends((int) $cashierId);

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'cashier-crediting'],
            [
                'label' => 'Crediting',
                'roles' => json_encode([
                    [
                        'id' => (int) $cashierId,
                        'name' => 'Cashier',
                    ],
                ]),
                'main_id' => null,
                'route' => '/cashier/credits',
                'component' => 'Web/cashierCreditPage',
                'icon' => 'IconCashBanknote',
                'order_no' => 2,
                'is_submenu' => false,
                'is_active' => true,
                'is_delete' => false,
                'updated_by' => 'System',
                'created_by' => 'System',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $permissionId = DB::table('list_permissions')->where('name', 'payroll.credits.update')->value('id');

        if ($permissionId) {
            DB::table('list_permission_routes')->updateOrInsert(
                ['route_name' => 'cashier.credits.update'],
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
        DB::table('list_routes')->where('slug', 'cashier-crediting')->delete();
        DB::table('list_permission_routes')->where('route_name', 'cashier.credits.update')->delete();
    }

    private function removeCashierFromStipends(int $cashierId): void
    {
        DB::table('list_routes')
            ->where('route', '/stipends')
            ->get()
            ->each(function ($route) use ($cashierId) {
                $roles = collect(json_decode($route->roles ?? '[]', true))
                    ->reject(fn ($role) => (int) ($role['id'] ?? 0) === $cashierId)
                    ->values()
                    ->all();

                DB::table('list_routes')
                    ->where('id', $route->id)
                    ->update([
                        'roles' => json_encode($roles),
                        'updated_at' => now(),
                    ]);
            });
    }
};
