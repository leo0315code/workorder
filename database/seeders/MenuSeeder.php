<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * 侧边栏菜单初始数据（幂等：按 所属端+路由名 匹配，可重复执行）
 *
 * 规则说明：
 * - module 非空 → 该菜单仅对拥有对应模块权限的客服显示
 * - admin_only → 仅管理员可见（用户/角色/设置/菜单管理）
 * - section → 侧栏分组用，同一 section 渲染在一块并配小标题
 * - 路由名已存在才会被渲染（MenuService 内 Route::has 防御）
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // ---- 客服端（agent）----
            ['audience' => 'agent', 'label' => '仪表盘',   'route_name' => 'dashboard',                   'icon' => 'dashboard', 'section' => '概览',   'sort' => 1],
            ['audience' => 'agent', 'label' => '工单',     'route_name' => 'tickets.index',              'icon' => 'ticket',    'section' => '概览',   'sort' => 2, 'active_pattern' => 'tickets.*', 'except_pattern' => 'tickets.create'],
            ['audience' => 'agent', 'label' => '客户档案', 'route_name' => 'admin.customers.index',      'icon' => 'customer',  'section' => '业务数据', 'sort' => 10, 'module' => 'customers'],
            ['audience' => 'agent', 'label' => '产品管理', 'route_name' => 'admin.products.index',       'icon' => 'product',   'section' => '业务数据', 'sort' => 20, 'module' => 'products'],
            ['audience' => 'agent', 'label' => '分类管理', 'route_name' => 'admin.categories.index',     'icon' => 'category',  'section' => '业务数据', 'sort' => 30, 'module' => 'categories'],
            ['audience' => 'agent', 'label' => '快捷回复', 'route_name' => 'admin.quick-replies.index',  'icon' => 'reply',     'section' => '客服工具', 'sort' => 40, 'module' => 'quick-replies'],
            ['audience' => 'agent', 'label' => '知识库',   'route_name' => 'admin.kb.index',              'icon' => 'chat',      'section' => '客服工具', 'sort' => 45],
            ['audience' => 'agent', 'label' => '工单模板', 'route_name' => 'admin.ticket-templates.index', 'icon' => 'ticket',  'section' => '客服工具', 'sort' => 50, 'module' => 'templates'],
            ['audience' => 'agent', 'label' => '数据报表', 'route_name' => 'admin.reports',              'icon' => 'chart',     'section' => '运营',     'sort' => 60, 'module' => 'reports'],

            // ---- 管理员专属（agent 端追加）----
            ['audience' => 'agent', 'label' => '用户管理', 'route_name' => 'admin.users.index',       'icon' => 'user',   'section' => '系统管理', 'sort' => 70, 'admin_only' => true],
            ['audience' => 'agent', 'label' => '角色管理', 'route_name' => 'admin.agent-roles.index', 'icon' => 'shield', 'section' => '系统管理', 'sort' => 80, 'admin_only' => true],
            ['audience' => 'agent', 'label' => '系统设置', 'route_name' => 'admin.settings',          'icon' => 'gear',   'section' => '系统管理', 'sort' => 90, 'admin_only' => true],
            ['audience' => 'agent', 'label' => '菜单管理', 'route_name' => 'admin.menus.index',       'icon' => 'list',   'section' => '系统管理', 'sort' => 100, 'admin_only' => true],

            // ---- 客户端（customer）----
            ['audience' => 'customer', 'label' => '仪表盘',   'route_name' => 'dashboard',      'icon' => 'dashboard', 'section' => '概览', 'sort' => 1],
            ['audience' => 'customer', 'label' => '我的工单', 'route_name' => 'tickets.index', 'icon' => 'ticket',    'section' => '概览', 'sort' => 2, 'active_pattern' => 'tickets.*', 'except_pattern' => 'tickets.create'],
        ];

        foreach ($items as $item) {
            Menu::updateOrCreate(
                ['audience' => $item['audience'], 'route_name' => $item['route_name']],
                $item
            );
        }
    }
}
