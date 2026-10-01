<?php

namespace App\Services\Dashboard;

use App\Models\ListPrograms;
use App\Models\ListRole;
use App\Models\Scholars;
use App\Models\SchoolCampuses;
use App\Models\User;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardService
{
    public function render(Request $request, $user, SystemPermissions $permissions)
    {
        return Inertia::render('Web/dashboardPage', [
            'dashboardType' => $permissions->dashboardType($user),
            'adminSummary' => [
                'total_users' => User::where('is_delete', false)->count(),
                'active_users' => User::where('is_delete', false)->where('is_active', true)->count(),
                'inactive_users' => User::where('is_delete', false)->where('is_active', false)->count(),
                'total_scholars' => Scholars::where('is_delete', false)->count(),
                'total_schools' => SchoolCampuses::where('is_delete', false)->count(),
                'total_programs' => ListPrograms::where('is_delete', false)->count(),
                'active_programs' => ListPrograms::where('is_delete', false)->where('is_active', true)->count(),
                'pending_users' => User::where('is_delete', false)
                    ->where('is_active', true)
                    ->where('is_verified', false)
                    ->count(),
                'role_distribution' => ListRole::where('is_delete', false)
                    ->withCount([
                        'users as active_users_count' => fn ($query) => $query
                            ->where('is_delete', false)
                            ->where('is_active', true),
                    ])
                    ->orderByDesc('active_users_count')
                    ->get(['id', 'name'])
                    ->map(fn ($role) => [
                        'name' => $role->name,
                        'count' => $role->active_users_count,
                    ])
                    ->values(),
                'recent_users' => User::with('role:id,name')
                    ->where('is_delete', false)
                    ->latest()
                    ->take(5)
                    ->get(['id', 'email', 'role_id', 'created_at', 'is_active', 'is_verified'])
                    ->map(fn ($account) => [
                        'email' => $account->email,
                        'role' => $account->role?->name ?? 'Unassigned',
                        'created_at' => $account->created_at?->format('M d, Y'),
                        'is_active' => (bool) $account->is_active,
                        'is_verified' => (bool) $account->is_verified,
                    ])
                    ->values(),
            ],
        ]);
    }
}
