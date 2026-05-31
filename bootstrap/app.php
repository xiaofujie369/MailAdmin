<?php

use App\Console\Commands\AggregateMailStats;
use App\Console\Commands\CollectMailEvents;
use App\Console\Commands\CreateAdminUser;
use App\Console\Commands\HealthCheckMailServer;
use App\Console\Commands\QueueSnapshot;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        CollectMailEvents::class,
        AggregateMailStats::class,
        QueueSnapshot::class,
        HealthCheckMailServer::class,
        CreateAdminUser::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
