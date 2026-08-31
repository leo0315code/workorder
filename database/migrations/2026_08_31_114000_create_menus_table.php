<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 侧边栏菜单表（DB 驱动）
 *
 * 说明：菜单条目存数据库，但路由/权限仍以代码为准——
 * - route_name 必须指向已注册的路由，否则渲染时自动跳过（防死链）
 * - module 绑定模块权限键（User::AGENT_MODULES），渲染时按 canAccessModule 过滤
 * - audience 区分客服端(agent)/客户端(customer)菜单；admin_only 为仅管理员可见项
 * - active_pattern 用于当前高亮（routeIs 模式），except_pattern 排除特定路由
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('audience', 10)->default('agent');          // agent | customer
            $table->boolean('admin_only')->default(false);             // 仅管理员可见
            $table->string('label', 50);                               // 显示名
            $table->string('route_name', 100)->nullable();             // 指向的路由名（可空=占位）
            $table->string('icon', 30)->default('ticket');             // nav-icon 组件图标名
            $table->string('module', 50)->nullable();                  // 模块权限键，null=不鉴权
            $table->string('active_pattern', 100)->nullable();         // 高亮 routeIs 模式，null=按路由前缀推导
            $table->string('except_pattern', 100)->nullable();         // 排除高亮的路由（如 tickets.create）
            $table->unsignedInteger('sort')->default(0);               // 排序（升序）
            $table->boolean('is_active')->default(true);               // 启停
            $table->timestamps();

            $table->index(['audience', 'is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
