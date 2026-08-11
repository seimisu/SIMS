<?php

namespace App\Services\Dashboard;

use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardPageService
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $permissions = app(SystemPermissions::class);

        if ($permissions->isRegionalRole($user)) {
            return app(RegionalDashboardService::class)->render($request, $user, $permissions);
        }

        if ($permissions->isSchoolCoordinator($user)) {
            return app(SchoolCoordinatorDashboardService::class)->render($request, $user, $permissions);
        }

        if ($permissions->isScholarshipReviewer($user)) {
            return app(ScholarshipDashboardService::class)->render($request, $user, $permissions);
        }

        return app(AdminDashboardService::class)->render($request, $user, $permissions);
    }
}
