<?php

declare(strict_types=1);

/**
 * Register 服务（可独立运行；Windows 下请单独启动本文件）
 * 运行：php websocket/start_register.php start
 */

use GatewayWorker\Register;
use Workerman\Worker;

if (! defined('WS_BOOTSTRAPPED')) {
    define('WS_BOOTSTRAPPED', true);
    require_once __DIR__.'/bootstrap.php';
}

$register = new Register('text://'.config('websocket.register_listen'));

// 被 start.php 组合模式引用时不重复启动
if (! defined('GLOBAL_START')) {
    Worker::runAll();
}
