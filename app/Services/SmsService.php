<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SmsCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 短信验证码服务（配置优先读系统设置 settings，未设置时兜底 .env/config）
 * - driver=demo   ：不真正发送，验证码写入日志并随接口返回（本地联调用）
 * - driver=aliyun/tencent ：生产接入点（需在系统设置页填写密钥/签名/模板）
 */
class SmsService
{
    /**
     * 读取短信配置：settings 优先，兜底 config/services.php
     */
    public static function cfg(string $key, mixed $default = null): mixed
    {
        $fromSettings = SettingService::get('sms_'.$key);

        return $fromSettings !== null ? $fromSettings : config('services.sms.'.$key, $default);
    }

    public static function driver(): string
    {
        return (string) self::cfg('driver', 'demo');
    }

    public static function sendCode(string $phone, string $ip = null): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        SmsCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
            'ip' => $ip,
        ]);

        $driver = self::driver();

        if ($driver === 'aliyun' || $driver === 'tencent') {
            // 生产接入点：调用阿里云/腾讯云短信 SDK（参数已可在系统设置页维护）
            // self::sendViaProvider($phone, $code);
            Log::info("sms via {$driver} not wired, code={$code} phone={$phone}");
        } else {
            Log::info("[demo sms] phone={$phone} code={$code}");
        }

        return $code;
    }

    public static function verify(string $phone, string $code): bool
    {
        // 演示万能验证码（生产请关闭）
        if ((bool) self::cfg('allow_demo_code', true) && $code === '123456') {
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
