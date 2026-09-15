<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')
            ->where('slug', 'cashier-crediting')
            ->update([
                'label' => 'Deposit',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'payroll.credits.view')
            ->update([
                'label' => 'Payroll Deposit - View',
                'description' => 'Allows viewing payroll deposit records.',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'payroll.credits.update')
            ->update([
                'label' => 'Payroll Deposit - Update',
                'description' => 'Allows updating deposit months.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('list_routes')
            ->where('slug', 'cashier-crediting')
            ->update([
                'label' => 'Crediting',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'payroll.credits.view')
            ->update([
                'label' => 'Payroll Credits - View',
                'description' => 'Allows viewing payroll crediting records.',
                'updated_at' => now(),
            ]);

        DB::table('list_permissions')
            ->where('name', 'payroll.credits.update')
            ->update([
                'label' => 'Payroll Credits - Update',
                'description' => 'Allows updating credited months.',
                'updated_at' => now(),
            ]);
    }
};
