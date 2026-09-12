<?php

use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'module' => EnsureModuleAccess::class,
        ]);

        // 全局安全响应头
        $middleware->prepend(SecurityHeaders::class);

        // 可信代理：nginx 反代后取真实客户端 IP（登录/API 限流按 IP 更准确）
        // 环境变量 TRUSTED_PROXIES 用逗号分隔（默认信任本机 nginx）
        $middleware->trustProxies(
            at: array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1'))
            ))),
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
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
