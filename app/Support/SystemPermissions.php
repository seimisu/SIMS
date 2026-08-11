<?php

namespace App\Support;

use App\Models\ListPermission;
use App\Models\ListPermissionRoute;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemPermissions
{
    public const ROLE_PERMISSIONS = [
        'administrator' => ['*'],

        'regional staff' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.update',
            'payroll.view',
            'payroll.update',
            'payroll.export',
            'payroll.submit',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'document-categories.manage',
            'video-resources.view',
            'video-resources.create',
            'video-resources.update',
            'video-resources.delete',
            'geolocation.upload',
        ],

        'regional supervisor' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.update',
            'payroll.view',
            'payroll.update',
            'payroll.export',
            'payroll.submit',
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'document-categories.manage',
            'video-resources.view',
            'video-resources.create',
            'video-resources.update',
            'video-resources.delete',
            'geolocation.upload',
        ],

        'scholarship staff' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.review',
            'profile-requests.view',
            'profile-requests.approve',
            'profile-requests.reject',
            'landbank-requests.view',
            'landbank-requests.approve',
            'landbank-requests.reject',
            'grade-submissions.view',
            'grade-submissions.approve',
            'grade-submissions.reject',
            'payroll.view',
            'payroll.export',
            'payroll.approve',
            'payroll.return',
            'payroll.recipients.manage-removal',
            'documents.view',
            'video-resources.view',
        ],

        'scholarship coordinator' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.review',
            'profile-requests.view',
            'profile-requests.approve',
            'profile-requests.reject',
            'landbank-requests.view',
            'landbank-requests.approve',
            'landbank-requests.reject',
            'grade-submissions.view',
            'grade-submissions.approve',
            'grade-submissions.reject',
            'payroll.view',
            'payroll.export',
            'payroll.approve',
            'payroll.return',
            'payroll.recipients.manage-removal',
            'documents.view',
            'video-resources.view',
        ],

        'school coordinator' => [
            'dashboard.view',
            'scholars.view',
            'scholars.update',
        ],
    ];

    public const ROUTE_PERMISSIONS = [
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
        'scholar.insert' => 'scholars.review',
        'scholar.destroy' => 'scholars.delete',
        'scholar.grade-update' => 'scholars.update',
        'scholar.grade-delete' => 'scholars.update',
        'scholars.update' => 'scholars.update',
        'scholars.activation' => 'scholars.activate',
        'scholars.transfer' => 'scholars.transfer',
        'review.validate' => 'scholars.review',
        'review.publish' => 'scholars.review',
        'scholar.grade-request' => 'grade-submissions.view',
        'scholar-academic-history.decision' => 'grade-submissions.view',
        'profile.request' => 'profile-requests.view',
        'landbank.request' => 'landbank-requests.view',

        'geolocation.store' => 'geolocation.upload',
        'stipends.payroll.update' => 'payroll.update',
        'stipends.recipients.mark-for-removal' => 'payroll.recipients.manage-removal',
        'stipends.recipients.cancel-removal' => 'payroll.recipients.manage-removal',
        'stipends.export' => 'payroll.export',
        'stipends.update' => 'payroll.view',

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

    public static function permissionDefinitions(): array
    {
        $names = collect(array_merge(
            array_values(self::ROUTE_PERMISSIONS),
            ...array_values(array_filter(
                self::ROLE_PERMISSIONS,
                fn ($permissions) => ! in_array('*', $permissions, true)
            ))
        ))
            ->unique()
            ->sort()
            ->values();

        return $names->mapWithKeys(function (string $name) {
            $group = Str::before($name, '.');
            $action = Str::of(Str::after($name, '.'))->replace('.', ' ')->headline()->toString();

            return [
                $name => [
                    'label' => Str::of($group)->headline().' - '.$action,
                    'group' => $group,
                    'description' => "Allows {$action} actions in the ".Str::of($group)->headline().' module.',
                ],
            ];
        })->all();
    }

    public function permissionsFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($this->isAdministrator($user)) {
            return $this->allPermissionNames();
        }

        if ($this->hasPermissionTables()) {
            return $user->role?->permissions()
                ->where('list_permissions.is_active', true)
                ->pluck('list_permissions.name')
                ->unique()
                ->values()
                ->all() ?? [];
        }

        $permissions = self::ROLE_PERMISSIONS[$this->roleName($user)] ?? [];
        if (in_array('*', $permissions, true)) {
            return $this->allPermissionNames();
        }

        return array_values(array_unique($permissions));
    }

    public function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if ($this->hasPermissionTables()) {
            return $user->role?->permissions()
                ->where('list_permissions.is_active', true)
                ->where('list_permissions.name', $permission)
                ->exists() ?? false;
        }

        $rolePermissions = self::ROLE_PERMISSIONS[$this->roleName($user)] ?? [];
        return in_array('*', $rolePermissions, true)
            || in_array($permission, $rolePermissions, true);
    }

    public function hasRole(?User $user, string $role): bool
    {
        return $user && $this->roleName($user) === Str::lower($role);
    }

    public function isAdministrator(?User $user): bool
    {
        return $this->hasRole($user, 'administrator');
    }

    public function isRegionalStaff(?User $user): bool
    {
        return $this->hasRole($user, 'regional staff');
    }

    public function isRegionalSupervisor(?User $user): bool
    {
        return $this->hasRole($user, 'regional supervisor');
    }

    public function isRegionalRole(?User $user): bool
    {
        return $this->isRegionalStaff($user) || $this->isRegionalSupervisor($user);
    }

    public function shouldScopeToRegion(?User $user): bool
    {
        return $this->isRegionalRole($user);
    }

    public function regionCodeFor(?User $user): ?string
    {
        return $user?->profile?->agency?->region_code;
    }

    public function agencyNameFor(?User $user): ?string
    {
        return $user?->profile?->agency?->name;
    }

    public function isSchoolCoordinator(?User $user): bool
    {
        return $this->hasRole($user, 'school coordinator');
    }

    public function isScholarshipReviewer(?User $user): bool
    {
        return $this->hasRole($user, 'scholarship staff')
            || $this->hasRole($user, 'scholarship coordinator');
    }

    public function dashboardType(?User $user): string
    {
        if ($this->isRegionalRole($user)) {
            return 'regional';
        }

        if ($this->isSchoolCoordinator($user)) {
            return 'school_coordinator';
        }

        if ($this->isAdministrator($user)) {
            return 'admin';
        }

        return 'default';
    }

    public function canAccessAgencyId(?User $user, ?int $agencyId): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if ($this->isRegionalRole($user)) {
            return (int) $user->profile?->agency_id === (int) $agencyId;
        }

        return true;
    }

    public function canAccessAgencyName(?User $user, ?string $agencyName): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        if ($this->isRegionalRole($user)) {
            return Str::lower($this->agencyNameFor($user) ?? '') === Str::lower($agencyName ?? '');
        }

        return true;
    }

    public function permissionForRoute(?string $routeName): ?string
    {
        if ($routeName && $this->hasPermissionRouteTables()) {
            $permission = ListPermissionRoute::query()
                ->where('route_name', $routeName)
                ->whereHas('permission', fn ($query) => $query->where('is_active', true))
                ->with('permission:id,name')
                ->first()
                ?->permission
                ?->name;

            if ($permission) {
                return $permission;
            }
        }

        return $routeName ? (self::ROUTE_PERMISSIONS[$routeName] ?? null) : null;
    }

    public function payrollBatchPermissions(User $user, object $batch, string $status): array
    {
        $canView = $this->can($user, 'payroll.view') && $this->canAccessPayrollRegion($user, $batch);
        $canViewGeneratedExcel = $canView && (
            $this->can($user, 'payroll.approve')
            || $this->can($user, 'payroll.return')
            || $this->can($user, 'payroll.recipients.manage-removal')
        );

        return [
            'canView' => $canView,
            'canEdit' => $this->canEditPayroll($user, $batch, $status),
            'canSubmit' => $this->canSubmitPayroll($user, $batch, $status),
            'canReview' => $this->canReviewPayroll($user, $status),
            'canApprove' => $this->can($user, 'payroll.approve') && $this->canReviewPayroll($user, $status),
            'canReject' => $this->can($user, 'payroll.return') && $this->canReviewPayroll($user, $status),
            'canViewGeneratedExcel' => $canViewGeneratedExcel,
            'canDelete' => false,
        ];
    }

    public function canEditPayroll(User $user, object $batch, string $status): bool
    {
        return $this->can($user, 'payroll.update')
            && $this->canAccessPayrollRegion($user, $batch)
            && in_array($status, ['draft', 'rejected_payroll'], true);
    }

    public function canSubmitPayroll(User $user, object $batch, string $status): bool
    {
        return $this->can($user, 'payroll.submit')
            && $this->canAccessPayrollRegion($user, $batch)
            && in_array($status, ['draft', 'rejected_payroll'], true);
    }

    public function canReviewPayroll(User $user, string $status): bool
    {
        return (
            $this->can($user, 'payroll.approve')
            || $this->can($user, 'payroll.return')
            || $this->can($user, 'payroll.recipients.manage-removal')
        )
            && $status === 'submitted_payroll';
    }

    private function canAccessPayrollRegion(User $user, object $batch): bool
    {
        if ($this->can($user, '*') || $this->roleName($user) === 'administrator') {
            return true;
        }

        if ($this->shouldScopeToRegion($user)) {
            return Str::lower($batch->region ?? '') === Str::lower($this->agencyNameFor($user) ?? '');
        }

        return true;
    }

    private function roleName(User $user): string
    {
        return Str::lower($user->role_array['name'] ?? '');
    }

    private function allPermissionNames(): array
    {
        if ($this->hasPermissionTables()) {
            return ListPermission::where('is_active', true)
                ->pluck('name')
                ->values()
                ->all();
        }

        return array_values(array_unique(array_merge(
            array_values(self::ROUTE_PERMISSIONS),
            ...array_values(array_filter(
                self::ROLE_PERMISSIONS,
                fn ($permissions) => ! in_array('*', $permissions, true)
            ))
        )));
    }

    private function hasPermissionTables(): bool
    {
        return Schema::hasTable('list_permissions')
            && Schema::hasTable('list_role_permissions');
    }

    private function hasPermissionRouteTables(): bool
    {
        return $this->hasPermissionTables()
            && Schema::hasTable('list_permission_routes');
    }
}
