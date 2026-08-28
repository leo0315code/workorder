<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 模块权限守卫：module:customers 等
 * 与菜单渲染共用 User::canAccessModule()
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessModule($module)) {
            abort(403);
        }

        return $next($request);
    }
}
