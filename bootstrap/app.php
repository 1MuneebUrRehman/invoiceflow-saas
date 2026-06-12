<?php

use App\Http\Middleware\SetCurrentTenant;
use App\Http\Middleware\SetCurrentTenantForApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['webhooks/stripe']);

        $middleware->web(append: [
            SetCurrentTenant::class,
        ]);

        // Tenant context must be set before implicit route-model binding so
        // the TenantScope applies to bound models — otherwise a request could
        // resolve another tenant's records by id.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetCurrentTenant::class,
        );

        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetCurrentTenantForApi::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
