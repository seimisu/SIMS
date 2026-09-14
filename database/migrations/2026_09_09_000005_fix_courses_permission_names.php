<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            DB::table('list_permissions')
                ->where('name', $oldName)
                ->update([
                    'name' => $newName,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->renames) as $oldName => $newName) {
            DB::table('list_permissions')
                ->where('name', $newName)
                ->update([
                    'name' => $oldName,
                    'updated_at' => now(),
                ]);
        }
    }
};
