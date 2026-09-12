<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use GatewayClient\Gateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 工单自动分配：负载均衡 + 在线优先策略
 * 只从「当前在线（已建立 WebSocket 连接）」的客服中，选取未完成工单数最少的；
 * 开关见系统设置 auto_assign
 */
class AutoAssignService
{
    /**
     * 在线 uid 集合提供器（测试注入用）。
     * 为 null 时走真实 Gateway 查询；测试可替换为闭包返回固定集合，避免依赖 WS 服务。
     *
     * @var (callable(): ?array<int, true>)|null
     */
    public static $onlineUidsProvider = null;

    /**
     * 返回被指派的客服 id；无在线客服或未开启时返回 null
     */
    public static function pick(): ?int
    {
        if (! SettingService::autoAssignEnabled()) {
            return null;
        }

        $candidates = User::whereIn('role', ['agent', 'admin'])
            ->where('manual_offline', false)
            ->withCount(['assignedTickets' => fn ($q) => $q->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])])
            ->orderByRaw("CASE WHEN role = 'agent' THEN 0 ELSE 1 END")
            ->orderBy('assigned_tickets_count')
            ->orderBy('id')
            ->get(['id']);

        if ($candidates->isEmpty()) {
            return null;
        }

        $online = self::onlineUids();

        return self::pickFromCandidates($candidates, $online);
    }

    /**
     * 纯选择逻辑（无外部依赖，可单测）：
     * - 在线集合为 null（实时服务不可用）→ 退回全量候选，取第一个
     * - 否则按候选顺序返回第一个在线的；无人在线返回 null
     *
     * @param  Collection<int, User>  $candidates  已按 agent>admin、负载升序、id 排序
     * @param  array<int, true>|null  $online  uid => true 的在线集合
     */
    public static function pickFromCandidates($candidates, ?array $online): ?int
    {
        if ($online === null) {
            return $candidates->first()?->id;
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
        // 测试注入：跳过真实 Gateway 查询
        if (self::$onlineUidsProvider !== null) {
            return (self::$onlineUidsProvider)();
        }

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
