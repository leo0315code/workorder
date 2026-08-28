<?php

declare(strict_types=1);

/**
 * Gateway 服务（浏览器 WebSocket 入口，可独立运行）
 * - ws://:6001   明文 WebSocket（本地联调 / 内网）
 * - wss://:6002  加密 WebSocket（HTTPS 生产环境，需 WS_SSL_ENABLED=true + 证书）
 * 运行：php websocket/start_gateway.php start
 */

use GatewayWorker\Gateway;
use Workerman\Worker;

if (! defined('WS_BOOTSTRAPPED')) {
    define('WS_BOOTSTRAPPED', true);
    require_once __DIR__.'/bootstrap.php';
}

$cfg = config('websocket');

/**
 * 构建一个 Gateway 实例（ws 或 wss）
 */
function makeGateway(string $transport, string $listen): Gateway
{
    // 应用层协议固定 websocket，传输层由 transport 决定（tcp 明文 / ssl 加密）
    // context 必须通过构造函数第二参传入（Gateway 将其设为 protected）；Workerman5 要求数组
    $context = [];
    if ($transport === 'ssl') {
        $ssl = config('websocket.ssl');
        $context = [
            'ssl' => [
                'local_cert' => $ssl['cert'],
                'local_pk' => $ssl['key'],
                'verify_peer' => (bool) $ssl['verify_peer'],
                'allow_self_signed' => true,
            ] + ($ssl['ca'] ? ['cafile' => $ssl['ca']] : []),
        ];
    }

    $gateway = new Gateway('websocket://'.$listen, $context);
    $gateway->name = $transport === 'ssl' ? 'TicketGatewayWSS' : 'TicketGatewayWS';
    $gateway->lanIp = config('websocket.gateway_lan_ip');
    // lanPort = startPort + worker id，两个 Gateway 的 id 均为 0 会撞内部端口，WSS 必须偏移
    $gateway->startPort = (int) config('websocket.gateway_start_port') + ($transport === 'ssl' ? 1000 : 0);
    // Windows 下 Workerman 仅支持单进程，强制 count=1；
    // SSL 传输在多进程共享端口场景下不稳，WSS 也固定单进程
    $gateway->count = (DIRECTORY_SEPARATOR === '\\' || $transport === 'ssl') ? 1 : (int) config('websocket.processes');
    $gateway->registerAddress = config('websocket.register_listen');
    // 心跳：55s 未收到数据则断开
    $gateway->pingInterval = 55;
    $gateway->pingNotResponseLimit = 1;
    $gateway->pingData = json_encode(['type' => 'ping']);

    if ($transport === 'ssl') {
        $gateway->transport = 'ssl';
    }

    return $gateway;
}

// 明文 WebSocket（始终启用）
makeGateway('websocket', $cfg['gateway_listen']);

// 加密 WebSocket（WS_SSL_ENABLED=true 时启用；证书缺失则给出告警）
if (! empty($cfg['ssl']['enabled']) && (bool) $cfg['ssl']['enabled']) {
    $ssl = $cfg['ssl'];
    if (! is_file($ssl['cert']) || ! is_file($ssl['key'])) {
        fwrite(STDERR, "[warn] WS_SSL_ENABLED=true 但证书不存在：{$ssl['cert']} / {$ssl['key']}，跳过 wss 监听\n");
    } else {
        makeGateway('ssl', $ssl['listen']);
    }
}

if (! defined('GLOBAL_START')) {
    Worker::runAll();
}
