<?php

namespace Tests\Feature;

use App\Support\SystemPermissions;
use Tests\TestCase;

class FinancialAssistancePermissionRoutesTest extends TestCase
{
    public function test_every_financial_assistance_route_has_a_permission_mapping(): void
    {
        $routeNames = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn (?string $name) => $name === 'stipends'
                || str_starts_with($name ?? '', 'stipends.')
                || $name === 'cashier.credits'
                || str_starts_with($name ?? '', 'cashier.credits.'))
            ->values();

        $unmappedRoutes = $routeNames
            ->reject(fn (string $name) => isset(SystemPermissions::ROUTE_PERMISSIONS[$name]))
            ->all();

        $this->assertNotEmpty($routeNames);
        $this->assertSame([], $unmappedRoutes, 'Unmapped Financial Assistance routes: '.implode(', ', $unmappedRoutes));
    }
}
