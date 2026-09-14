<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $routePermissions = [
            'roles.store' => 'roles.create',
            'roles.update' => 'roles.update',
            'roles.destroy' => 'roles.delete',
            'routes.store' => 'routes.create',
            'routes.update' => 'routes.update',
            'routes.destroy' => 'routes.delete',
            'users.store' => 'users.create',
            'users.resend' => 'users.resend-activation',
            'users.update' => 'users.update',
            'users.destroy' => 'users.delete',
            'programs.store' => 'programs.create',
            'programs.update' => 'programs.update',
            'programs.destroy' => 'programs.delete',
            'status.store' => 'statuses.create',
            'status.update' => 'statuses.update',
            'status.destroy' => 'statuses.delete',
            'location.regions.store' => 'locations.create',
            'location.regions.update' => 'locations.update',
            'location.regions.destroy' => 'locations.delete',
            'location.provinces.store' => 'locations.create',
            'location.provinces.update' => 'locations.update',
            'location.provinces.destroy' => 'locations.delete',
            'location.cities.store' => 'locations.create',
            'location.cities.update' => 'locations.update',
            'location.cities.destroy' => 'locations.delete',
            'location.barangays.store' => 'locations.create',
            'location.barangays.update' => 'locations.update',
            'location.barangays.destroy' => 'locations.delete',
            'academic.courses.store' => 'academic.create',
            'academic.courses.update' => 'academic.update',
            'academic.courses.destroy' => 'academic.delete',
            'academic.references.store' => 'academic.create',
            'academic.references.update' => 'academic.update',
            'academic.references.destroy' => 'academic.delete',
            'academic.universities.store' => 'schools.create',
            'academic.universities.update' => 'schools.update',
            'academic.universities.destroy' => 'schools.delete',
            'academic.universities.course.store' => 'schools.create',
            'academic.universities.course.update' => 'schools.update',
            'academic.universities.course.destroy' => 'schools.delete',
            'academic.universities.grade.store' => 'schools.create',
            'academic.universities.grade.update' => 'schools.update',
            'academic.universities.grade.destroy' => 'schools.delete',
            'campus.info.store' => 'schools.create',
            'campus.info.update' => 'schools.update',
            'campus.info.destroy' => 'schools.delete',
            'campus.semester.store' => 'schools.create',
            'campus.semester.update' => 'schools.update',
            'campus.semester.destroy' => 'schools.delete',
            'campus.curriculum.store' => 'schools.create',
            'campus.curriculum.update' => 'schools.update',
            'campus.curriculum.destroysubject' => 'schools.delete',
            'campus.curriculum.destroyCurriculum' => 'schools.delete',
            'campus.curriculum.copy' => 'schools.curriculum.copy',
            'campus.curriculum.paste' => 'schools.curriculum.paste',
            'scholar.store' => 'scholars.create',
            'scholar.destroy' => 'scholars.delete',
            'scholars.activation' => 'scholars.activate',
            'scholars.transfer' => 'scholars.transfer',
            'scholar.grade-request' => 'grade-submissions.view',
            'profile.request' => 'profile-requests.view',
            'landbank.request' => 'landbank-requests.view',
            'documents.store' => 'documents.create',
            'documents.update' => 'documents.update',
            'documents.destroy' => 'documents.delete',
            'document-categories.store' => 'document-categories.manage',
            'document-categories.update' => 'document-categories.manage',
            'document-categories.destroy' => 'document-categories.manage',
            'video-resources.store' => 'video-resources.create',
            'video-resources.update' => 'video-resources.update',
            'video-resources.destroy' => 'video-resources.delete',
        ];

        $expandedAssignments = [
            'roles.manage' => ['roles.create', 'roles.update', 'roles.delete', 'roles.assign-permissions'],
            'routes.manage' => ['routes.create', 'routes.update', 'routes.delete'],
            'users.manage' => ['users.create', 'users.update', 'users.delete', 'users.resend-activation'],
            'programs.manage' => ['programs.create', 'programs.update', 'programs.delete'],
            'statuses.manage' => ['statuses.create', 'statuses.update', 'statuses.delete'],
            'locations.manage' => ['locations.create', 'locations.update', 'locations.delete'],
            'academic.manage' => ['academic.create', 'academic.update', 'academic.delete'],
            'schools.manage' => ['schools.create', 'schools.update', 'schools.delete', 'schools.curriculum.copy', 'schools.curriculum.paste'],
            'scholars.manage' => ['scholars.create', 'scholars.delete'],
            'documents.manage' => ['documents.create', 'documents.update', 'documents.delete', 'document-categories.manage'],
            'video-resources.manage' => ['video-resources.create', 'video-resources.update', 'video-resources.delete'],
            'scholars.requests.review' => [
                'profile-requests.view',
                'profile-requests.approve',
                'profile-requests.reject',
                'landbank-requests.view',
                'landbank-requests.approve',
                'landbank-requests.reject',
                'grade-submissions.view',
                'grade-submissions.approve',
                'grade-submissions.reject',
            ],
            'scholars.update' => ['scholars.activate', 'scholars.transfer'],
        ];

        $newPermissions = collect($routePermissions)
            ->values()
            ->merge(collect($expandedAssignments)->flatten())
            ->unique()
            ->values();

        $newPermissions->each(fn (string $name) => $this->upsertPermission($name));

        foreach ($expandedAssignments as $from => $targets) {
            foreach ($targets as $target) {
                $this->copyRoleAssignments($from, $target);
            }
        }

        foreach ($routePermissions as $routeName => $permissionName) {
            $permissionId = DB::table('list_permissions')->where('name', $permissionName)->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('list_permission_routes')->updateOrInsert(
                ['route_name' => $routeName],
                [
                    'permission_id' => $permissionId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('list_permissions')
            ->whereIn('name', array_keys($expandedAssignments))
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $legacyPermissions = [
            'roles.manage',
            'routes.manage',
            'users.manage',
            'programs.manage',
            'statuses.manage',
            'locations.manage',
            'academic.manage',
            'schools.manage',
            'scholars.manage',
            'documents.manage',
            'video-resources.manage',
            'scholars.requests.review',
        ];

        collect($legacyPermissions)->each(fn (string $name) => $this->upsertPermission($name));

        $rollbackAssignments = [
            'roles.create' => 'roles.manage',
            'routes.create' => 'routes.manage',
            'users.create' => 'users.manage',
            'programs.create' => 'programs.manage',
            'statuses.create' => 'statuses.manage',
            'locations.create' => 'locations.manage',
            'academic.create' => 'academic.manage',
            'schools.create' => 'schools.manage',
            'scholars.create' => 'scholars.manage',
            'documents.create' => 'documents.manage',
            'video-resources.create' => 'video-resources.manage',
            'profile-requests.approve' => 'scholars.requests.review',
            'landbank-requests.approve' => 'scholars.requests.review',
            'grade-submissions.approve' => 'scholars.requests.review',
        ];

        foreach ($rollbackAssignments as $from => $to) {
            $this->copyRoleAssignments($from, $to);
        }

        $legacyRoutePermissions = [
            'roles.store' => 'roles.manage',
            'roles.update' => 'roles.manage',
            'roles.destroy' => 'roles.manage',
            'routes.store' => 'routes.manage',
            'routes.update' => 'routes.manage',
            'routes.destroy' => 'routes.manage',
            'users.store' => 'users.manage',
            'users.resend' => 'users.manage',
            'users.update' => 'users.manage',
            'users.destroy' => 'users.manage',
            'programs.store' => 'programs.manage',
            'programs.update' => 'programs.manage',
            'programs.destroy' => 'programs.manage',
            'status.store' => 'statuses.manage',
            'status.update' => 'statuses.manage',
            'status.destroy' => 'statuses.manage',
            'location.regions.store' => 'locations.manage',
            'location.regions.update' => 'locations.manage',
            'location.regions.destroy' => 'locations.manage',
            'location.provinces.store' => 'locations.manage',
            'location.provinces.update' => 'locations.manage',
            'location.provinces.destroy' => 'locations.manage',
            'location.cities.store' => 'locations.manage',
            'location.cities.update' => 'locations.manage',
            'location.cities.destroy' => 'locations.manage',
            'location.barangays.store' => 'locations.manage',
            'location.barangays.update' => 'locations.manage',
            'location.barangays.destroy' => 'locations.manage',
            'academic.courses.store' => 'academic.manage',
            'academic.courses.update' => 'academic.manage',
            'academic.courses.destroy' => 'academic.manage',
            'academic.references.store' => 'academic.manage',
            'academic.references.update' => 'academic.manage',
            'academic.references.destroy' => 'academic.manage',
            'academic.universities.store' => 'schools.manage',
            'academic.universities.update' => 'schools.manage',
            'academic.universities.destroy' => 'schools.manage',
            'scholar.store' => 'scholars.manage',
            'scholar.destroy' => 'scholars.manage',
            'scholar.grade-request' => 'scholars.requests.review',
            'profile.request' => 'scholars.requests.review',
            'landbank.request' => 'scholars.requests.review',
            'documents.store' => 'documents.manage',
            'documents.update' => 'documents.manage',
            'documents.destroy' => 'documents.manage',
            'document-categories.store' => 'documents.manage',
            'document-categories.update' => 'documents.manage',
            'document-categories.destroy' => 'documents.manage',
            'video-resources.store' => 'video-resources.manage',
            'video-resources.update' => 'video-resources.manage',
            'video-resources.destroy' => 'video-resources.manage',
        ];

        foreach ($legacyRoutePermissions as $routeName => $permissionName) {
            $permissionId = DB::table('list_permissions')->where('name', $permissionName)->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('list_permission_routes')->updateOrInsert(
                ['route_name' => $routeName],
                [
                    'permission_id' => $permissionId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function upsertPermission(string $name): void
    {
        $group = Str::before($name, '.');
        $action = Str::of(Str::after($name, '.'))->replace('.', ' ')->headline()->toString();

        DB::table('list_permissions')->updateOrInsert(
            ['name' => $name],
            [
                'label' => Str::of($group)->headline().' - '.$action,
                'group_name' => $group,
                'description' => "Allows {$action} actions in the ".Str::of($group)->headline().' module.',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function copyRoleAssignments(string $fromPermission, string $toPermission): void
    {
        $fromId = DB::table('list_permissions')->where('name', $fromPermission)->value('id');
        $toId = DB::table('list_permissions')->where('name', $toPermission)->value('id');

        if (! $fromId || ! $toId) {
            return;
        }

        DB::table('list_role_permissions')
            ->where('permission_id', $fromId)
            ->pluck('role_id')
            ->each(function ($roleId) use ($toId) {
                DB::table('list_role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $toId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }
};
