<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * 允许的角色（支持数组），不满足则 403。
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // admin 拥有全部权限；未显式指定角色时仅要求登录
        if (empty($roles) || in_array($user->role, $roles) || $user->isAdmin()) {
            return $next($request);
        }

        abort(403, '没有权限访问该页面');
    }
}
