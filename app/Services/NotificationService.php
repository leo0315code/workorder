<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

/**
 * 站内通知服务：入库 + GatewayWorker 实时推送（推送到目标用户 uid）
 */
class NotificationService
{
    /**
     * 给单个用户发通知（入库 + 推送）
     */
    public static function notifyUser(int $userId, string $title, ?string $body = null, ?string $link = null): ?UserNotification
    {
        $notification = UserNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);

        // 实时推送：目标用户若在线立即收到
        WebSocketService::pushToUid($userId, [
            'type' => 'notification',
            'notification' => [
                'id' => $notification->id,
                'title' => $title,
                'body' => $body,
                'link' => $link,
                'created_at' => $notification->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);

        return $notification;
    }

    /**
     * 群发给多个用户
     */
    public static function notifyUsers(array $userIds, string $title, ?string $body = null, ?string $link = null): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            try {
                self::notifyUser((int) $userId, $title, $body, $link);
            } catch (\Throwable $e) {
                Log::warning('notification send failed: '.$e->getMessage());
            }
        }
    }
}
