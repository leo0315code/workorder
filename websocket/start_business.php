<?php

declare(strict_types=1);

/**
 * BusinessWorker 服务（业务处理，可独立运行）
 * 运行：php websocket/start_business.php start
 */

use App\Ws\Events;
use GatewayWorker\BusinessWorker;
use Workerman\Worker;

if (! defined('WS_BOOTSTRAPPED')) {
    define('WS_BOOTSTRAPPED', true);
    require_once __DIR__.'/bootstrap.php';
}

$cfg = config('websocket');

$businessWorker = new BusinessWorker();
$businessWorker->name = 'TicketBusiness';
$businessWorker->eventHandler = Events::class;
$businessWorker->registerAddress = $cfg['register_listen'];

if (! defined('GLOBAL_START')) {
    Worker::runAll();
}
