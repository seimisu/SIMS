<?php

use App\Support\SystemPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncPermissions();
        $this->attachCashierToCreditingRoute();
    }

    public function down(): void
    {
        $cashierId = DB::table('list_roles')->where('slug', 'cashier')->value('id');

        if ($cashierId) {
            DB::table('list_role_permissions')
                ->where('role_id', $cashierId)
                ->whereIn('permission_id', DB::table('list_permissions')
                    ->whereIn('name', ['payroll.credits.view', 'payroll.credits.update'])
                    ->pluck('id'))
                ->delete();

            DB::table('list_routes')
                ->where('route', '/cashier/credits')
                ->get()
                ->each(function ($route) use ($cashierId) {
                    $roles = collect(json_decode($route->roles ?? '[]', true))
                        ->reject(fn ($role) => (int) ($role['id'] ?? 0) === (int) $cashierId)
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
    }

    private function syncPermissions(): void
    {
        foreach (SystemPermissions::permissionDefinitions() as $name => $definition) {
            DB::table('list_permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'label' => $definition['label'],
                    'group_name' => $definition['group'],
                    'description' => $definition['description'] ?? null,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

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

        $cashierId = DB::table('list_roles')->where('slug', 'cashier')->value('id');

        if (! $cashierId) {
            return;
        }

        DB::table('list_permissions')
            ->whereIn('name', SystemPermissions::ROLE_PERMISSIONS['cashier'] ?? [])
            ->pluck('id')
            ->each(function ($permissionId) use ($cashierId) {
                DB::table('list_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $cashierId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }

    private function attachCashierToCreditingRoute(): void
    {
        $cashierId = DB::table('list_roles')->where('slug', 'cashier')->value('id');

        if (! $cashierId) {
            return;
        }

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
                'slug' => 'cashier-crediting',
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
    }
};
