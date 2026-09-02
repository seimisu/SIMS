<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoles = json_encode([
            ['id' => 1, 'name' => 'Administrator'],
        ]);

        $roles = DB::table('list_routes')->where('slug', 'scholars')->value('roles') ?: $adminRoles;

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'scholar-submissions'],
            [
                'label' => 'Scholar Submissions',
                'roles' => $roles,
                'main_id' => null,
                'route' => '/scholar-submissions',
                'component' => 'Web/scholarSubmissionsPage',
                'icon' => 'IconInbox',
                'order_no' => 4,
                'is_submenu' => false,
                'is_active' => true,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('list_routes')->where('slug', 'scholar-submissions')->delete();
    }
};
