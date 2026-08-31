<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 侧边栏菜单（DB 驱动）
 *
 * 存菜单条目（label/路由名/图标/权限/排序），路由与权限仍在代码侧；
 * 渲染逻辑见 MenuService::sidebarFor()。
 */
class Menu extends Model
{
    use HasFactory;

    public const AUDIENCE_AGENT = 'agent';
    public const AUDIENCE_CUSTOMER = 'customer';

    protected $fillable = [
        'audience', 'admin_only', 'label', 'route_name',
        'icon', 'module', 'active_pattern', 'except_pattern',
        'sort', 'is_active',
    ];

    protected $casts = [
        'admin_only' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];
}
