<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $submissionRoles = json_encode([
            ['id' => 1, 'name' => 'Administrator'],
            ['id' => 4, 'name' => 'regional staff'],
        ]);

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'scholar-submission-management'],
            [
                'label' => 'Submissions',
                'roles' => $submissionRoles,
                'main_id' => null,
                'route' => null,
                'component' => null,
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

        $parentId = DB::table('list_routes')->where('slug', 'scholar-submission-management')->value('id');

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'scholar-submissions'],
            [
                'label' => 'Grade Submissions',
                'roles' => $submissionRoles,
                'main_id' => $parentId,
                'route' => '/scholar-submissions',
                'component' => 'Web/scholarSubmissionsPage',
                'icon' => 'IconReportAnalytics',
                'order_no' => 1,
                'is_submenu' => true,
                'is_active' => true,
                'is_delete' => false,
                'updated_by' => 'System',
                'updated_at' => now(),
            ]
        );

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'scholar-profile-requests'],
            [
                'label' => 'Profile Requests',
                'roles' => $submissionRoles,
                'main_id' => $parentId,
                'route' => '/scholar-profile-requests',
                'component' => 'Web/scholarProfileRequestsPage',
                'icon' => 'IconUserEdit',
                'order_no' => 2,
                'is_submenu' => true,
                'is_active' => true,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'scholar-landbank-requests'],
            [
                'label' => 'Landbank Requests',
                'roles' => $submissionRoles,
                'main_id' => $parentId,
                'route' => '/scholar-landbank-requests',
                'component' => 'Web/scholarLandbankRequestsPage',
                'icon' => 'IconBuildingBank',
                'order_no' => 3,
                'is_submenu' => true,
                'is_active' => true,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->mapRoutePermission('scholar-submissions', 'grade-submissions.view');
        $this->mapRoutePermission('scholar-profile-requests', 'profile-requests.view');
        $this->mapRoutePermission('scholar-landbank-requests', 'landbank-requests.view');
    }

    public function down(): void
    {
        DB::table('list_routes')
            ->where('slug', 'scholar-submissions')
            ->update([
                'label' => 'Submissions',
                'main_id' => null,
                'icon' => 'IconInbox',
                'order_no' => 4,
                'is_submenu' => false,
                'updated_by' => 'System',
                'updated_at' => now(),
            ]);

        DB::table('list_routes')
            ->whereIn('slug', [
                'scholar-profile-requests',
                'scholar-landbank-requests',
                'scholar-submission-management',
            ])
            ->delete();

        DB::table('list_permission_routes')
            ->whereIn('route_name', [
                'scholar-profile-requests',
                'scholar-landbank-requests',
            ])
            ->delete();
    }

    private function mapRoutePermission(string $routeName, string $permissionName): void
    {
        $permissionId = DB::table('list_permissions')->where('name', $permissionName)->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('list_permission_routes')->updateOrInsert(
            ['route_name' => $routeName],
            [
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
