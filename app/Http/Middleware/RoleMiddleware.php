<?php

namespace App\Http\Middleware;

use App\Models\ListRoutes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    private array $routeAccess = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cacheKey = ($request->user()->role_id ?? 'guest').'|'.$request->getPathInfo();

        $check = $this->routeAccess[$cacheKey] ??= ListRoutes::where('is_delete', false)
            ->where('is_active', true)
            ->whereRaw("
        EXISTS (
            SELECT 1
            FROM json_array_elements(roles) elem
            WHERE (elem->>'id')::int = ?
        )
    ", [$request->user()->role_id ?? null])
            ->where('route', $request->getPathInfo())
            ->exists();

        if (! $check) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
