<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 站内通知服务：入库 + GatewayWorker 实时推送 + 可选邮件提醒
 */
class NotificationService
{
    /**
     * 给单个用户发通知（入库 + 推送 + 可选邮件）
     */
    public static function notifyUser(int $userId, string $title, ?string $body = null, ?string $link = null): ?UserNotification
    {
        $notification = UserNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'link' => self::normalizeLink($link),
        ]);

        // 实时推送：目标用户若在线立即收到
        WebSocketService::pushToUid($userId, [
            'type' => 'notification',
            'notification' => [
                'id' => $notification->id,
                'title' => $title,
                'body' => $body,
                'link' => self::normalizeLink($link),
                'created_at' => $notification->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);

        // 邮件提醒（系统设置开启时）
        self::sendEmailIfEnabled($userId, $title, $body, self::normalizeLink($link));

        return $notification;
    }

    /**
     * 将绝对 URL 转成应用内相对路径，兼容任意域（http/127.0.0.1 或 https/虚拟域名）
     * - http://127.0.0.1:8000/tickets/1 -> /tickets/1
     * - https://new-order.test/ws        -> /ws
     * - null 或 非 http URL 原样保留
     */
    public static function normalizeLink(?string $link): ?string
    {
        if ($link === null || $link === '') {
            return $link;
        }
        if (! preg_match('#^https?://#i', $link)) {
            return $link;
        }
        $parts = parse_url($link);
        if (! isset($parts['host'])) {
            return $link;
        }
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $path.$query.$fragment;
    }

    /**
     * 群发给多个用户
     */
    public static function notifyUsers(array $userIds, string $title, ?string $body = null, ?string $link = null): void
    {
        // 群发只需规范化一次
        $link = self::normalizeLink($link);
        foreach (array_unique(array_filter($userIds)) as $userId) {
            try {
                self::notifyUser((int) $userId, $title, $body, $link);
            } catch (\Throwable $e) {
                Log::warning('notification send failed: '.$e->getMessage());
            }
        }
    }

    /**
     * 邮件提醒：设置页开启 email_notify_enabled 且用户有邮箱时发送
     * MAIL_MAILER=log（默认）时写入日志；生产配 SMTP 后真实发送
     */
    protected static function sendEmailIfEnabled(int $userId, string $title, ?string $body, ?string $link): void
    {
        if (SettingService::get('email_notify_enabled', '0') !== '1') {
            return;
        }

        $user = User::find($userId);
        if (! $user?->email) {
            return;
        }

        try {
            Mail::raw(
                ($body ? $body."\n\n" : '').($link ? '查看详情：'.$link : '')."\n\n—— 来自 ".SettingService::siteName().' 自动提醒',
                function ($message) use ($user, $title) {
                    $message->to($user->email)
                        ->subject('【'.SettingService::siteName().'】'.$title);
                }
            );
        } catch (\Throwable $e) {
            Log::warning('email notify failed: '.$e->getMessage());
        }
    }
}
