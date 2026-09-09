<?php

use App\Support\SystemPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (SystemPermissions::permissionDefinitions() as $name => $definition) {
            if (! str_starts_with($name, 'payroll.')) {
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
            'payroll.update' => 'Payroll - Update',
            'payroll.return' => 'Payroll - Return',
            'payroll.credits.view' => 'Payroll - Credits View',
            'payroll.credits.update' => 'Payroll - Credits Update',
            'payroll.recipients.manage-removal' => 'Payroll - Recipients Manage Removal',
        ];

        foreach ($labels as $name => $label) {
            DB::table('list_permissions')
                ->where('name', $name)
                ->update([
                    'label' => $label,
                    'group_name' => 'payroll',
                    'description' => 'Allows '.str_replace('Payroll - ', '', $label).' actions in the Payroll module.',
                    'updated_at' => now(),
                ]);
        }
    }
};
