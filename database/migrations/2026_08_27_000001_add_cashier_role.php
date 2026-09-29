<?php

use App\Support\SystemPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_roles')->updateOrInsert(
            ['slug' => 'cashier'],
            [
                'name' => 'Cashier',
                'description' => 'Access to cashier dashboard tools.',
                'is_active' => true,
                'is_lock' => false,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $cashierId = DB::table('list_roles')->where('slug', 'cashier')->value('id');

        if ($cashierId) {
            $this->attachRoleToDashboard((int) $cashierId);
            $this->syncCashierPermissions((int) $cashierId);
        }
    }

    public function down(): void
    {
        $cashierId = DB::table('list_roles')->where('slug', 'cashier')->value('id');

        if ($cashierId) {
            $dashboard = DB::table('list_routes')->where('slug', 'dashboard')->first();

            if ($dashboard) {
                $roles = collect(json_decode($dashboard->roles ?? '[]', true))
                    ->reject(fn ($role) => (int) ($role['id'] ?? 0) === (int) $cashierId)
                    ->values()
                    ->all();

                DB::table('list_routes')
                    ->where('id', $dashboard->id)
                    ->update([
                        'roles' => json_encode($roles),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('list_role_permissions')->where('role_id', $cashierId)->delete();
            DB::table('list_roles')->where('id', $cashierId)->delete();
        }
    }

    private function attachRoleToDashboard(int $cashierId): void
    {
        $dashboard = DB::table('list_routes')->where('slug', 'dashboard')->first();

        if (! $dashboard) {
            return;
        }

        $roles = collect(json_decode($dashboard->roles ?? '[]', true));

        if (! $roles->pluck('id')->map(fn ($id) => (int) $id)->contains($cashierId)) {
            $roles->push([
                'id' => $cashierId,
                'name' => 'Cashier',
            ]);
        }

        DB::table('list_routes')
            ->where('id', $dashboard->id)
            ->update([
                'roles' => json_encode($roles->values()->all()),
                'updated_at' => now(),
            ]);
    }

    private function syncCashierPermissions(int $cashierId): void
    {
        $permissionNames = SystemPermissions::ROLE_PERMISSIONS['cashier'] ?? [];
        $permissionIds = DB::table('list_permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('list_role_permissions')->updateOrInsert(
                [
                    'role_id' => $cashierId,
                    'permission_id' => $permissionId,
                ],
                []
            );
        }
    }
};
