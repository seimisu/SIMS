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
        $permission = $permissions->permissionForRoute($request->route()?->getName());

        if ($permission && ! $permissions->can($request->user(), $permission)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
