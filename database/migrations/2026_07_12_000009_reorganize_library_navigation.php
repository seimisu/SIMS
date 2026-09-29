<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoles = json_encode([
            [
                'id' => 1,
                'name' => 'Administrator',
            ],
        ]);

        $referenceRoles = DB::table('list_routes')->where('slug', 'reference-library')->value('roles') ?: $adminRoles;
        $schoolRoles = DB::table('list_routes')->where('slug', 'universities')->value('roles') ?: $adminRoles;

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'reference-library'],
            [
                'label' => 'References',
                'roles' => $referenceRoles,
                'main_id' => null,
                'route' => null,
                'component' => null,
                'icon' => 'IconBook2',
                'order_no' => 5,
                'is_submenu' => false,
                'is_active' => true,
                'is_delete' => false,
                'updated_by' => 'System',
                'updated_at' => now(),
            ]
        );

        $referencesId = DB::table('list_routes')->where('slug', 'reference-library')->value('id');

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'schools-courses'],
            [
                'label' => 'Schools & Courses',
                'roles' => $schoolRoles,
                'main_id' => null,
                'route' => null,
                'component' => null,
                'icon' => 'IconSchool',
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

        $schoolsCoursesId = DB::table('list_routes')->where('slug', 'schools-courses')->value('id');

        DB::table('list_routes')->updateOrInsert(
            ['slug' => 'content-library'],
            [
                'label' => 'Content Library',
                'roles' => $adminRoles,
                'main_id' => null,
                'route' => null,
                'component' => null,
                'icon' => 'IconFolder',
                'order_no' => 6,
                'is_submenu' => false,
                'is_active' => true,
                'is_delete' => false,
                'created_by' => 'System',
                'updated_by' => 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $contentLibraryId = DB::table('list_routes')->where('slug', 'content-library')->value('id');

        $this->updateRoutes([
            'dashboard' => ['label' => 'Dashboard'],
            'admin-setting' => ['label' => 'Admin Settings'],
            'places' => ['label' => 'Locations'],
            'backup-restore' => ['label' => 'Backup & Restore'],
            'roles' => ['label' => 'User Roles'],
            'routes' => ['label' => 'Navigation Settings'],
            'user-management' => ['label' => 'User Management'],
            'barangay' => ['label' => 'Barangay'],
            'cities' => ['label' => 'Cities'],
            'provinces' => ['label' => 'Provinces'],
            'regions' => ['label' => 'Regions'],
            'universities' => [
                'label' => 'Schools',
                'main_id' => $schoolsCoursesId,
                'order_no' => 1,
                'is_submenu' => true,
            ],
            'courses' => [
                'label' => 'Courses',
                'main_id' => $schoolsCoursesId,
                'order_no' => 2,
                'is_submenu' => true,
            ],
            'scholar-status' => [
                'label' => 'Statuses',
                'main_id' => $referencesId,
                'order_no' => 1,
                'is_submenu' => true,
            ],
            'scholar-program' => [
                'label' => 'Programs',
                'main_id' => $referencesId,
                'order_no' => 2,
                'is_submenu' => true,
            ],
            'academic-references' => [
                'label' => 'Academic References',
                'main_id' => $referencesId,
                'order_no' => 3,
                'is_submenu' => true,
            ],
            'documents' => [
                'label' => 'Downloadables',
                'main_id' => $contentLibraryId,
                'order_no' => 1,
                'is_submenu' => true,
            ],
            'video-resources' => [
                'label' => 'Video Library',
                'main_id' => $contentLibraryId,
                'order_no' => 2,
                'is_submenu' => true,
            ],
        ]);
    }

    public function down(): void
    {
        $referencesId = DB::table('list_routes')->where('slug', 'reference-library')->value('id');

        $this->updateRoutes([
            'reference-library' => ['label' => 'Reference Library'],
            'universities' => ['main_id' => null, 'order_no' => 3, 'is_submenu' => false],
            'courses' => ['main_id' => $referencesId, 'order_no' => 5, 'is_submenu' => true],
            'scholar-status' => ['main_id' => $referencesId, 'order_no' => 5, 'is_submenu' => true],
            'scholar-program' => ['main_id' => $referencesId, 'order_no' => 5, 'is_submenu' => true],
            'academic-references' => ['label' => 'References', 'main_id' => $referencesId, 'order_no' => 5, 'is_submenu' => true],
            'documents' => ['main_id' => $referencesId, 'order_no' => 6, 'is_submenu' => true],
            'video-resources' => ['main_id' => $referencesId, 'order_no' => 7, 'is_submenu' => true],
        ]);

        DB::table('list_routes')->whereIn('slug', ['schools-courses', 'content-library'])->delete();
    }

    private function updateRoutes(array $routes): void
    {
        foreach ($routes as $slug => $values) {
            DB::table('list_routes')
                ->where('slug', $slug)
                ->update([
                    ...$values,
                    'updated_by' => 'System',
                    'updated_at' => now(),
                ]);
        }
    }
};
