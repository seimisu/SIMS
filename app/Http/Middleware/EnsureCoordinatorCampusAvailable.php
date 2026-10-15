<?php

namespace App\Http\Middleware;

use App\Models\SchoolCampuses;
use App\Support\SystemPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoordinatorCampusAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && app(SystemPermissions::class)->isSchoolCoordinator($user)
            && ! $request->routeIs('logout', 'profile', 'profile.*', 'user.changePassword')
            && (! $user->school_id || ! SchoolCampuses::notDeleted()->whereKey($user->school_id)->exists())) {
            abort(403, 'Your assigned campus is unavailable. Please contact an administrator to update your campus assignment.');
        }

        return $next($request);
    }
}
