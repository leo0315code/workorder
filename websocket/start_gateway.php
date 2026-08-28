<?php

declare(strict_types=1);

/**
 * Gateway 服务（浏览器 WebSocket 入口，可独立运行）
 * 运行：php websocket/start_gateway.php start
 */

use GatewayWorker\Gateway;
use Workerman\Worker;

if (! defined('WS_BOOTSTRAPPED')) {
    define('WS_BOOTSTRAPPED', true);
    require_once __DIR__.'/bootstrap.php';
}

$cfg = config('websocket');

$gateway = new Gateway('websocket://'.$cfg['gateway_listen']);
$gateway->name = 'TicketGateway';
$gateway->lanIp = $cfg['gateway_lan_ip'];
$gateway->startPort = (int) $cfg['gateway_start_port'];
// Windows 下 Workerman 仅支持单进程，强制 count=1
$gateway->count = DIRECTORY_SEPARATOR === '\\' ? 1 : (int) $cfg['processes'];
$gateway->registerAddress = $cfg['register_listen'];
// 心跳：55s 未收到数据则断开
$gateway->pingInterval = 55;
$gateway->pingNotResponseLimit = 1;
$gateway->pingData = json_encode(['type' => 'ping']);

if (! defined('GLOBAL_START')) {
    Worker::runAll();
}
