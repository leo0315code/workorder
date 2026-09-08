<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

if (! function_exists('ticket_route')) {
    /**
     * 工单路由名：客服/管理员走带前缀的后台别名（admin.tickets.*），客户走全局（tickets.*）。
     * 解决：后台侧边栏用 /console/tickets，但视图/代码内硬编码 route('tickets.show') 会生成无前缀 URL。
     *
     * 用法（按当前用户角色）：
     *   ticket_route('show', $ticket)
     *   ticket_route('index')
     *
     * 用法（按指定接收者角色，用于通知链接——客服收通知应指向 /console/tickets/{id}）：
     *   ticket_route('show', $ticket, ['for' => $recipientUser])
     *   ticket_route('show', $ticket, ['for_role' => 'agent'])
     */
    function ticket_route(string $action, mixed ...$params): string
    {
        // 末位参数可能是 ['for' => User] 或 ['for_role' => 'agent']，用于指定接收者
        $forUser = null;
        $forRole = null;

        $last = $params ? end($params) : null;
        if (is_array($last) && isset($last['for'])) {
            $forUser = $last['for'];
            unset($params[array_key_last($params)]);
        } elseif (is_array($last) && isset($last['for_role'])) {
            $forRole = $last['for_role'];
            unset($params[array_key_last($params)]);
        }

        $isAgent = $forUser
            ? ($forUser->isAgent() ?? false)
            : ($forRole
                ? $forRole === 'agent' || $forRole === 'admin'
                : (Auth::user()?->isAgent() ?? false));

        $name = $isAgent ? 'admin.tickets.'.$action : 'tickets.'.$action;

        // 防御：admin.tickets.* 未注册时回退全局（例如测试环境路由被裁剪）
        if (! Route::has($name)) {
            $name = 'tickets.'.$action;
        }

        return $params ? route($name, ...$params) : route($name);
    }
}
