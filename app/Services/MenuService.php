<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * 侧边栏菜单服务（DB 驱动）
 *
 * 渲染规则（与旧硬编码逻辑完全一致）：
 * - 按所属端（agent/customer）过滤
 * - admin_only 项仅管理员可见
 * - module 非空时按 User::canAccessModule() 鉴权
 * - route_name 指向的路由不存在时跳过（防 DB 误配导致死链）
 * - 高亮：active_pattern（routeIs）优先，缺省按路由前缀推导；except_pattern 排除
 */
class MenuService
{
    /**
     * 生成当前用户可见的侧栏菜单（与布局渲染直接兼容的数组结构）
     *
     * @return array<int, array{label:string, route:string|null, icon:string, active:bool}>
     */
    public static function sidebarFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $audience = $user->isAgent() ? Menu::AUDIENCE_AGENT : Menu::AUDIENCE_CUSTOMER;

        return Menu::where('is_active', true)
            ->where('audience', $audience)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->filter(fn (Menu $m) => ! $m->admin_only || $user->isAdmin())
            ->filter(fn (Menu $m) => ! $m->module || $user->canAccessModule($m->module))
            ->filter(fn (Menu $m) => ! $m->route_name || Route::has($m->route_name))
            ->map(fn (Menu $m) => [
                'label' => $m->label,
                'route' => $m->route_name,
                'icon' => $m->icon,
                'section' => $m->section,
                'active' => self::isActive($m),
            ])
            ->values()
            ->all();
    }

    /**
     * 当前路由是否命中该菜单的高亮规则
     */
    protected static function isActive(Menu $menu): bool
    {
        $current = request()->route()?->getName();

        if (! $current || ! $menu->route_name) {
            return false;
        }

        $pattern = $menu->active_pattern ?: self::defaultPattern($menu->route_name);

        $active = $pattern !== null && request()->routeIs($pattern);

        if ($active && $menu->except_pattern) {
            $active = ! request()->routeIs($menu->except_pattern);
        }

        return $active;
    }

    /**
     * 缺省高亮模式：由路由名推导
     * - dashboard            → dashboard（精确）
     * - tickets.index        → tickets.*
     * - admin.customers.index → admin.customers.*
     */
    protected static function defaultPattern(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if (str_ends_with($routeName, '.index')) {
            return substr($routeName, 0, -6).'.*';
        }

        return $routeName;
    }
}
