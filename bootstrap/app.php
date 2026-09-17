<?php

use App\Http\Middleware\SetActiveBranch;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'setLocale' => SetLocale::class,
        ]);

        $middleware->appendToGroup('web', SetActiveBranch::class);

        // Laravel's default middlewarePriority list bubbles Authenticate
        // ahead of any custom alias not in that list — including
        // 'setLocale' — regardless of route registration order. Without
        // this pin, an unauthenticated request's redirect-to-login runs
        // before URL::defaults(['locale' => ...]) is ever set, and
        // route('login') 500s. See App\Http\Middleware\SetLocale docblock.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: SetLocale::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
