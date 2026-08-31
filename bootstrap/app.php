<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'module' => EnsureModuleAccess::class,
        ]);
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule): void {
        // 每日 09:00 巡检：SLA 超时工单 + 售后临期/过期客户 + 待认领升级提醒
        // 生产环境需挂 crontab：* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
        $schedule->command('support:scan-daily')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/support-scan.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
