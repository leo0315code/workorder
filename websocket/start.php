<?php

declare(strict_types=1);

/**
 * GatewayWorker 组合启动脚本（Unix/Linux/macOS 多进程模式）
 * 运行：php websocket/start.php start | stop | restart | status（支持 -d 守护）
 *
 * Windows 服务器请分别启动（每文件单进程，不支持 -d 守护）：
 *   php websocket/start_register.php start
 *   php websocket/start_gateway.php start
 *   php websocket/start_business.php start
 * 或直接使用 `php artisan ws:start`（自动适配平台）。
 *
 * 架构：
 *   Register(1238)  <- GatewayClient 从这里获取 Gateway 地址（服务端推送）
 *   Gateway(6001)   <- 浏览器 WebSocket 连接入口
 *   BusinessWorker  <- 业务进程，处理 onMessage 等事件
 */

use Workerman\Worker;

if (! defined('WS_BOOTSTRAPPED')) {
    define('WS_BOOTSTRAPPED', true);
    require_once __DIR__.'/bootstrap.php';
}

define('GLOBAL_START', true);

require_once __DIR__.'/start_register.php';
require_once __DIR__.'/start_gateway.php';
require_once __DIR__.'/start_business.php';

Worker::runAll();
