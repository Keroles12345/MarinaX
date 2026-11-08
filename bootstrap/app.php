<?php

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
$middleware->appendToGroup('web', \App\Http\Middleware\InitializeTenantContext::class);
$middleware->appendToGroup('web', \App\Http\Middleware\SetDashboardLocale::class);
$middleware->alias([
'staff'=>\App\Http\Middleware\StaffMiddleware::class,
'admin'=>\App\Http\Middleware\AdminMiddleware::class,
'superadmin'=>\App\Http\Middleware\SuperAdminMiddleware::class,
'accounting-role'=>\App\Http\Middleware\AccountingRoleMiddleware::class,
'supervisor'=>\App\Http\Middleware\SupervisorMiddleware::class,
'system-permission'=>\App\Http\Middleware\SystemPermissionMiddleware::class,
'StaffSales'=>\App\Http\Middleware\StaffSallesMiddleware::class,
'StaffOperations'=>\App\Http\Middleware\StaffOperationsMiddleware::class,
'StaffReseller'=>\App\Http\Middleware\StaffResellerMiddleware::class,
   'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
