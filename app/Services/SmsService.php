<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SmsCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 短信验证码服务
 * - driver=demo   ：不真正发送，验证码写入日志并随接口返回（本地联调用）
 * - driver=log    ：仅写日志
 * - driver=aliyun/tencent ：需配置对应 SDK（生产接入点，见 .env 注释）
 */
class SmsService
{
    public static function sendCode(string $phone, string $ip = null): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        SmsCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
            'ip' => $ip,
        ]);

        $driver = config('services.sms.driver', 'demo');

        if ($driver === 'aliyun' || $driver === 'tencent') {
            // 生产接入点：调用阿里云/腾讯云短信 SDK
            // self::sendViaProvider($phone, $code);
            Log::info("sms via {$driver} not wired, code={$code} phone={$phone}");
        } else {
            Log::info("[demo sms] phone={$phone} code={$code}");
        }

        return $code;
    }

    public static function verify(string $phone, string $code): bool
    {
        // 演示万能验证码（生产务必移除）
        if (config('services.sms.allow_demo_code') && $code === '123456') {
            return true;
        }

        $row = SmsCode::valid($phone, $code)->latest('id')->first();

        if (! $row) {
            return false;
        }

        $row->update(['used_at' => now()]);

        return true;
    }

    /**
     * 随机登录验证码（未注册手机号自动建号时的初始密码）
     */
    public static function randomPassword(): string
    {
        return Str::random(12);
    }
}
