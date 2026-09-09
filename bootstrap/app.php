<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'company'    => \App\Http\Middleware\CompanyMiddleware::class,
            'permission' => \App\Http\Middleware\HasPermission::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'meta/webhook',
            'api/webhook',
            'api/webhook/*',
            'webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
