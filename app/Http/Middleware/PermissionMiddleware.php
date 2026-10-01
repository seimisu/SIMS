<?php

namespace App\Http\Middleware;

use App\Support\SystemPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $permissions = app(SystemPermissions::class);
        $routeName = $request->route()?->getName();
        $permission = $permissions->permissionForRoute($routeName);

        if (! $permission && $this->requiresPermissionMapping($routeName)) {
            abort(403, 'Permission mapping is not configured for this route.');
        }

        if ($permission && ! $permissions->can($request->user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }

    private function requiresPermissionMapping(?string $routeName): bool
    {
        return $routeName === 'stipends'
            || str_starts_with($routeName ?? '', 'stipends.')
            || $routeName === 'cashier.credits'
            || str_starts_with($routeName ?? '', 'cashier.credits.');
    }
}
