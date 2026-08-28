<?php

declare(strict_types=1);

namespace App\Services;

use GatewayClient\Gateway;
use Illuminate\Support\Facades\Log;

/**
 * WebSocket 推送服务（GatewayClient -> GatewayWorker）
 */
class WebSocketService
{
    /**
     * 前端浏览器应使用的 WebSocket 地址（自动适配 ws / wss）
     * - VITE_WS_URL 显式配置时优先（可写死完整地址）
     * - WS_PROXY_PATH 配置时：走同源反向代理（生产推荐，TLS 由 Nginx/Apache 终结），
     *   如 wss://new-order.test/ws，需在 Web 服务器把该路径 Upgrade 转发到 ws://127.0.0.1:6001
     * - 否则按当前页面协议直连：HTTPS 页面 → wss://host:6002，HTTP → ws://host:6001
     */
    public static function frontendWsUrl(): string
    {
        $configured = env('VITE_WS_URL');
        if ($configured) {
            return $configured;
        }

        $scheme = request()->isSecure() ? 'wss' : 'ws';
        $host = request()->getHost();

        $proxyPath = (string) env('WS_PROXY_PATH');
        if ($proxyPath !== '') {
            return $scheme.'://'.$host.'/'.ltrim($proxyPath, '/');
        }

        $listen = $scheme === 'wss' ? (string) (config('websocket.ssl.listen') ?? '0.0.0.0:6002') : (string) config('websocket.gateway_listen');
        $port = substr((string) strrchr($listen, ':'), 1);

        return $scheme.'://'.$host.':'.($port ?: '6001');
    }
    /**
     * 推送消息到房间（如 ticket.12）
     */
    public static function pushToRoom(string $room, array $payload): bool
    {
        self::boot();

        try {
            Gateway::sendToGroup($room, self::encode($payload));

            return true;
        } catch (\Throwable $e) {
            Log::warning('ws pushToRoom failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * 推送给指定用户（按 uid）
     */
    public static function pushToUid(int $uid, array $payload): bool
    {
        self::boot();

        try {
            Gateway::sendToUid((string) $uid, self::encode($payload));

            return true;
        } catch (\Throwable $e) {
            Log::warning('ws pushToUid failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * 生成客户端鉴权签名（与 App\Ws\Events::signature 一致）
     */
    public static function signature(int $uid, array $rooms): string
    {
        $rooms = array_values(array_filter($rooms, 'is_string'));
        sort($rooms);

        return hash_hmac('sha256', $uid.'|'.implode(',', $rooms), (string) config('websocket.secret'));
    }

    private static function encode(array $payload): string
    {
        return json_encode(array_merge(['ts' => time()], $payload), JSON_UNESCAPED_UNICODE);
    }

    public static function boot(): void
    {
        // 注意：GatewayClient 默认 registerAddress 为 127.0.0.1:1236（非空），必须无条件覆盖
        Gateway::$registerAddress = (string) config('websocket.gateway_client_register');
    }
}
