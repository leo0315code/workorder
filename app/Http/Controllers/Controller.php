<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * 登录后首页：客服/管理员 → 带前缀的后台首页（admin.dashboard，如 /console）；
     * 客户 → 全局仪表盘（/dashboard）。
     */
    protected function homeRoute(): string
    {
        return Auth::user()?->isAgent() ? 'admin.dashboard' : 'dashboard';
    }
}
