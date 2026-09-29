<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'documents'],
            [
                'label' => 'Downloadables',
                'roles' => json_encode([
                    [
                        'id' => 1,
                        'name' => 'Administrator',
                    ],
                ]),
                'main_id' => 5,
                'route' => '/documents',
                'component' => 'Web/documentPage',
                'icon' => 'IconFileDescription',
                'order_no' => 6,
                'is_submenu' => true,
                'is_active' => true,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('list_routes')->where('slug', 'documents')->delete();
    }
};
