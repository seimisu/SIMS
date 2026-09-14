<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private array $renames = [
        'academic.create' => 'courses.create',
        'academic.update' => 'courses.update',
        'academic.delete' => 'courses.delete',
    ];

    public function up(): void
    {
        foreach ($this->renames as $oldName => $newName) {
            $this->renamePermission($oldName, $newName);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->renames) as $oldName => $newName) {
            $this->renamePermission($newName, $oldName);
        }
    }

    private function renamePermission(string $oldName, string $newName): void
    {
        $old = DB::table('list_permissions')->where('name', $oldName)->first();
        $new = DB::table('list_permissions')->where('name', $newName)->first();

        if (! $old && ! $new) {
            $this->upsertPermission($newName);
            return;
        }

        if ($old && ! $new) {
            DB::table('list_permissions')
                ->where('id', $old->id)
                ->update($this->permissionValues($newName));

            return;
        }

        if ($old && $new) {
            DB::table('list_role_permissions')
                ->where('permission_id', $old->id)
                ->pluck('role_id')
                ->each(function ($roleId) use ($new) {
                    DB::table('list_role_permissions')->updateOrInsert(
                        [
                            'role_id' => $roleId,
                            'permission_id' => $new->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });

            DB::table('list_permission_routes')
                ->where('permission_id', $old->id)
                ->update([
                    'permission_id' => $new->id,
                    'updated_at' => now(),
                ]);

            DB::table('list_role_permissions')->where('permission_id', $old->id)->delete();
            DB::table('list_permissions')->where('id', $old->id)->delete();
        }

        DB::table('list_permissions')
            ->where('name', $newName)
            ->update($this->permissionValues($newName));
    }

    private function upsertPermission(string $name): void
    {
        DB::table('list_permissions')->updateOrInsert(
            ['name' => $name],
            $this->permissionValues($name)
        );
    }

    private function permissionValues(string $name): array
    {
        $group = Str::before($name, '.');
        $action = Str::of(Str::after($name, '.'))->replace('.', ' ')->headline()->toString();

        return [
            'name' => $name,
            'label' => Str::of($group)->headline().' - '.$action,
            'group_name' => $group,
            'description' => "Allows {$action} actions in the ".Str::of($group)->headline().' module.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
};
