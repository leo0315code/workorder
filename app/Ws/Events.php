<?php

declare(strict_types=1);

namespace App\Ws;

use GatewayWorker\Lib\Gateway;
use Throwable;

/**
 * GatewayWorker 业务事件处理器
 *
 * 客户端连接后发送鉴权消息：
 * {
 *   "type": "auth",
 *   "uid": 1,
 *   "rooms": ["ticket.1", "ticket.2", "ticket.all"],
 *   "token": "<hmac_sha256 签名>"
 * }
 * token = hash_hmac('sha256', uid . '|' . implode(',', rooms), config('websocket.secret'))
 */
class Events
{
    public static function onWorkerStart($businessWorker): void
    {
        // 业务进程启动钩子（可在此加载服务）
    }

    public static function onConnect($client_id): void
    {
        // 连接建立（鉴权在 onMessage 中完成）
    }

    public static function onMessage($client_id, $data): void
    {
        $msg = json_decode((string) $data, true);

        if (! is_array($msg) || ($msg['type'] ?? '') !== 'auth') {
            Gateway::sendToClient($client_id, self::json([
                'type' => 'error', 'message' => 'invalid message',
            ]));

            return;
        }

        $uid = (int) ($msg['uid'] ?? 0);
        $rooms = array_values(array_filter((array) ($msg['rooms'] ?? []), 'is_string'));
        $token = (string) ($msg['token'] ?? '');

        if ($uid <= 0 || $token === '') {
            Gateway::sendToClient($client_id, self::json([
                'type' => 'auth_fail', 'message' => 'missing params',
            ]));

            return;
        }

        $expected = self::signature($uid, $rooms);

        if (! hash_equals($expected, $token)) {
            Gateway::sendToClient($client_id, self::json([
                'type' => 'auth_fail', 'message' => 'invalid token',
            ]));

            return;
        }

        // 绑定 uid（点对点推送），并加入房间
        Gateway::bindUid($client_id, (string) $uid);
        foreach ($rooms as $room) {
            Gateway::joinGroup($client_id, (string) $room);
        }

        Gateway::sendToClient($client_id, self::json([
            'type' => 'auth_ok',
            'uid' => $uid,
            'rooms' => $rooms,
        ]));
    }

    public static function onClose($client_id): void
    {
        // GatewayWorker 会自动清理 uid / group 绑定
    }

    public static function onWorkerStop($businessWorker): void
    {
    }

    /**
     * 生成与 Laravel 侧一致的 HMAC 签名。
     */
    public static function signature(int $uid, array $rooms): string
    {
        $secret = (string) config('websocket.secret');
        sort($rooms);

        return hash_hmac('sha256', $uid.'|'.implode(',', $rooms), $secret);
    }

    private static function json(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
