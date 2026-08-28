<?php

declare(strict_types=1);

/**
 * 共享引导：加载 Composer autoload + Laravel 容器
 * 供 start.php / start_register.php / start_gateway.php / start_business.php 复用
 */

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
