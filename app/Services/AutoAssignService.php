<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;

/**
 * 工单自动分配：负载均衡策略
 * 从未完成工单数最少的客服/管理员中选取；开关见系统设置 auto_assign
 */
class AutoAssignService
{
    /**
     * 返回被指派的客服 id；未开启或没有可用客服时返回 null
     * 策略：优先普通客服（agent），其次管理员；同角色内按未完成工单数最少优先
     */
    public static function pick(): ?int
    {
        if (! SettingService::autoAssignEnabled()) {
            return null;
        }

        return User::whereIn('role', ['agent', 'admin'])
            ->withCount(['assignedTickets' => fn ($q) => $q->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])])
            ->orderByRaw("CASE WHEN role = 'agent' THEN 0 ELSE 1 END")
            ->orderBy('assigned_tickets_count')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * 已开启自动分配？
     */
    public static function enabled(): bool
    {
        return SettingService::autoAssignEnabled();
    }
}
