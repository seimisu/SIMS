<?php

use App\Http\Middleware\EnsureCoordinatorCampusAvailable;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Preserve contact-number whitespace so validation rejects it rather than silently trimming it.
        $middleware->trimStrings(except: ['phone', 'contact_no']);
        $middleware->web(append: [
            EnsureCoordinatorCampusAvailable::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\UniqueConstraintViolationException $exception, \Illuminate\Http\Request $request) {
            $field = \App\Support\NamedEntryUniqueness::constraintField($exception->getMessage());
            if ($field === null) {
                return null;
            }
            $message = $field === 'slug'
                ? 'This slug already exists. Please enter a different slug.'
                : 'This name already exists. Please enter a different name.';
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
            }

            return redirect()->back()->withErrors([$field => $message]);
        });
    })->create();
