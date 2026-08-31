<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * 短信发送失败（通道返回错误码 / 网络异常 / 配置缺失）
 */
class SmsSendException extends RuntimeException
{
}
