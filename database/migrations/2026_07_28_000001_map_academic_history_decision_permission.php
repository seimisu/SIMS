<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('list_permissions')
            ->where('name', 'grade-submissions.view')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('list_permission_routes')->updateOrInsert(
            ['route_name' => 'scholar-academic-history.decision'],
            [
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('list_permission_routes')
            ->where('route_name', 'scholar-academic-history.decision')
            ->delete();
    }
};
