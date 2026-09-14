<?php

namespace App\Services\Dashboard;

use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
        return Inertia::render('Web/dashboardPage', [
            'dashboardType' => $permissions->dashboardType($user),
        ]);
    }
}
