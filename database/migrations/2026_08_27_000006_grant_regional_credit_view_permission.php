<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('list_permissions')
            ->where('name', 'payroll.credits.view')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('list_roles')
            ->whereIn(DB::raw('LOWER(name)'), ['regional staff', 'regional supervisor'])
            ->pluck('id')
            ->each(function ($roleId) use ($permissionId) {
                DB::table('list_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('list_permissions')
            ->where('name', 'payroll.credits.view')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('list_role_permissions')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', DB::table('list_roles')
                ->whereIn(DB::raw('LOWER(name)'), ['regional staff', 'regional supervisor'])
                ->pluck('id'))
            ->delete();
    }
};
