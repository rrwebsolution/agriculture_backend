<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
         $middleware->validateCsrfTokens(except: [
            'api/login', 
            'api/logout',
            'api/register',
            'api/roles',
            'api/roles/*',
            'api/clusters',
            'api/clusters/*',
            'api/users-store',
            'api/users-update/*',
            'api/users-delete/*',
            'api/farmers',
            'api/farmers/*',
            'api/barangays',
            'api/barangays/*',
            'api/fisherfolks',
            'api/fisherfolks/*',
            'api/crops',
            'api/crops/*',
            'api/cooperatives',
            'api/cooperatives/*',
            'api/farm-locations',
            'api/farm-locations/*',
            'broadcasting/auth',
            'api/update-password',
            'api/plantings',
            'api/plantings/*',
            'api/planting-history/*',
            'api/system/storage-info',
            'api/harvests',
            'api/harvests/*',
            'api/fisheries',
            'api/fisheries/*',
            'api/equipments',
            'api/equipments/*',
            'api/inventory',
            'api/inventory/*',

        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'token.not_expired' => \App\Http\Middleware\EnsureTokenNotExpired::class,
            'token.device_match' => \App\Http\Middleware\EnsureTokenDeviceMatches::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
