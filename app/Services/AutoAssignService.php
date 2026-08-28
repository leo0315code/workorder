<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use GatewayClient\Gateway;
use Illuminate\Support\Facades\Log;

/**
 * 工单自动分配：负载均衡 + 在线优先策略
 * 只从「当前在线（已建立 WebSocket 连接）」的客服中，选取未完成工单数最少的；
 * 开关见系统设置 auto_assign
 */
class AutoAssignService
{
    /**
     * 返回被指派的客服 id；无在线客服或未开启时返回 null
     */
    public static function pick(): ?int
    {
        if (! SettingService::autoAssignEnabled()) {
            return null;
        }

        $candidates = User::whereIn('role', ['agent', 'admin'])
            ->withCount(['assignedTickets' => fn ($q) => $q->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])])
            ->orderByRaw("CASE WHEN role = 'agent' THEN 0 ELSE 1 END")
            ->orderBy('assigned_tickets_count')
            ->orderBy('id')
            ->get(['id']);

        if ($candidates->isEmpty()) {
            return null;
        }

        $online = self::onlineUids();

        // 在线集合为 null（实时服务不可用）时不过滤，退回全量候选
        if ($online === null) {
            return $candidates->first()->id;
        }

        foreach ($candidates as $candidate) {
            if (isset($online[$candidate->id])) {
                return $candidate->id;
            }
        }

        return null;
    }

    /**
     * 当前在线（绑定 uid 且保持 WebSocket 连接）的用户 id 集合
     * 实时服务不可用时返回 null
     */
    public static function onlineUids(): ?array
    {
        try {
            WebSocketService::boot();

            return array_fill_keys(Gateway::getAllUidList(), true);
        } catch (\Throwable $e) {
            Log::warning('ws online check failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * 已开启自动分配？
     */
    public static function enabled(): bool
    {
        return SettingService::autoAssignEnabled();
    }
}

