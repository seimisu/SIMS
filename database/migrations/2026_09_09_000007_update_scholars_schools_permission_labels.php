<?php

use App\Support\SystemPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissionNames = [
        'scholars.landbank.view-sensitive',
        'schools.curriculum.copy',
        'schools.curriculum.paste',
    ];

    public function up(): void
    {
        $definitions = SystemPermissions::permissionDefinitions();

        foreach ($this->permissionNames as $name) {
            $definition = $definitions[$name] ?? null;

            if (! $definition) {
                continue;
            }

            DB::table('list_permissions')
                ->where('name', $name)
                ->update([
                    'label' => $definition['label'],
                    'group_name' => $definition['group'],
                    'description' => $definition['description'] ?? null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $labels = [
            'scholars.landbank.view-sensitive' => 'Scholars - Landbank View Sensitive',
            'schools.curriculum.copy' => 'Schools - Curriculum Copy',
            'schools.curriculum.paste' => 'Schools - Curriculum Paste',
        ];

        foreach ($labels as $name => $label) {
            DB::table('list_permissions')
                ->where('name', $name)
                ->update([
                    'label' => $label,
                    'updated_at' => now(),
                ]);
        }
    }
};
