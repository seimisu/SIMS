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
            'payroll.create',
            'payroll.edit',
            'payroll.submit',
            'payroll.delete',
            'geolocation.upload',
        ],

        'regional supervisor' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.update',
            'payroll.view',
            'payroll.create',
            'payroll.edit',
            'payroll.submit',
            'payroll.delete',
            'geolocation.upload',
        ],

        'scholarship staff' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.update',
            'scholars.review',
            'scholars.requests.review',
            'payroll.view',
            'payroll.review',
            'payroll.approve',
            'payroll.reject',
        ],

        'scholarship coordinator' => [
            'dashboard.view',
            'schools.view',
            'scholars.view',
            'scholars.update',
            'scholars.review',
            'scholars.requests.review',
            'payroll.view',
            'payroll.review',
            'payroll.approve',
            'payroll.reject',
        ],

        'school coordinator' => [
            'dashboard.view',
            'scholars.view',
            'scholars.update',
        ],
    ];

    public const ROUTE_PERMISSIONS = [
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
        'academic.universities.course.store' => 'schools.manage',
        'academic.universities.course.update' => 'schools.manage',
        'academic.universities.course.destroy' => 'schools.manage',
        'academic.universities.grade.store' => 'schools.manage',
        'academic.universities.grade.update' => 'schools.manage',
        'academic.universities.grade.destroy' => 'schools.manage',
        'campus.info.store' => 'schools.manage',
        'campus.info.update' => 'schools.manage',
        'campus.info.destroy' => 'schools.manage',
        'campus.semester.store' => 'schools.manage',
        'campus.semester.update' => 'schools.manage',
        'campus.semester.destroy' => 'schools.manage',
        'campus.curriculum.store' => 'schools.manage',
        'campus.curriculum.update' => 'schools.manage',
        'campus.curriculum.destroysubject' => 'schools.manage',
        'campus.curriculum.destroyCurriculum' => 'schools.manage',
        'campus.curriculum.copy' => 'schools.manage',
        'campus.curriculum.paste' => 'schools.manage',

        'scholar.store' => 'scholars.manage',
        'scholar.insert' => 'scholars.review',
        'scholar.destroy' => 'scholars.manage',
        'scholar.grade-update' => 'scholars.update',
        'scholar.grade-delete' => 'scholars.update',
        'scholars.update' => 'scholars.update',
        'scholars.activation' => 'scholars.update',
        'scholars.transfer' => 'scholars.update',
        'review.validate' => 'scholars.review',
        'review.publish' => 'scholars.review',
        'scholar.grade-request' => 'scholars.requests.review',
        'profile.request' => 'scholars.requests.review',
        'landbank.request' => 'scholars.requests.review',

        'geolocation.store' => 'geolocation.upload',
        'stipends.store' => 'payroll.create',
        'stipends.recipients.store' => 'payroll.edit',
        'stipends.payroll.update' => 'payroll.edit',
        'stipends.export' => 'payroll.view',
        'stipends.update' => 'payroll.view',
        'stipends.destroy' => 'payroll.delete',
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
            return ListPermissionRoute::query()
                ->where('route_name', $routeName)
                ->whereHas('permission', fn ($query) => $query->where('is_active', true))
                ->with('permission:id,name')
                ->first()
                ?->permission
                ?->name;
        }

        return $routeName ? (self::ROUTE_PERMISSIONS[$routeName] ?? null) : null;
    }

    public function payrollBatchPermissions(User $user, object $batch, string $status): array
    {
        return [
            'canView' => $this->can($user, 'payroll.view') && $this->canAccessPayrollRegion($user, $batch),
            'canEdit' => $this->canEditPayroll($user, $batch, $status),
            'canSubmit' => $this->canEditPayroll($user, $batch, $status),
            'canReview' => $this->canReviewPayroll($user, $status),
            'canApprove' => $this->can($user, 'payroll.approve') && $this->canReviewPayroll($user, $status),
            'canReject' => $this->can($user, 'payroll.reject') && $this->canReviewPayroll($user, $status),
            'canDelete' => $this->can($user, 'payroll.delete')
                && $this->canAccessPayrollRegion($user, $batch)
                && in_array($status, ['draft', 'rejected_payroll'], true),
        ];
    }

    public function canEditPayroll(User $user, object $batch, string $status): bool
    {
        return $this->can($user, 'payroll.edit')
            && $this->canAccessPayrollRegion($user, $batch)
            && in_array($status, ['draft', 'rejected_payroll'], true);
    }

    public function canReviewPayroll(User $user, string $status): bool
    {
        return $this->can($user, 'payroll.review') && $status === 'submitted_payroll';
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
