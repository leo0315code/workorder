<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 微信扫码登录服务
 * - 真实模式：配置 WECHAT_APPID / WECHAT_SECRET 后走开放平台扫码回调
 * - 演示模式：未配置时，前端展示"模拟扫码"按钮，本地可直接跑通绑定/登录链路
 */
class WechatService
{
    public const CACHE_PREFIX = 'wechat_qr_';

    public static function enabled(): bool
    {
        return (bool) config('services.wechat.appid');
    }

    /**
     * 生成一次扫码会话，返回 scene 标识与二维码内容
     */
    public static function createSession(): array
    {
        $scene = Str::uuid()->toString();
        $state = [
            'status' => 'pending',
            'openid' => null,
            'created_at' => now()->timestamp,
        ];

        Cache::put(self::CACHE_PREFIX.$scene, $state, now()->addMinutes(5));

        return [
            'scene' => $scene,
            // 演示模式用随机 openid 模拟；真实模式为空，等回调写入
            'qr_url' => self::enabled()
                ? self::qrConnectUrl($scene)
                : null,
        ];
    }

    public static function getState(string $scene): ?array
    {
        return Cache::get(self::CACHE_PREFIX.$scene);
    }

    public static function updateState(string $scene, array $data): void
    {
        $state = self::getState($scene) ?? [];
        Cache::put(self::CACHE_PREFIX.$scene, array_merge($state, $data), now()->addMinutes(5));
    }

    public static function forget(string $scene): void
    {
        Cache::forget(self::CACHE_PREFIX.$scene);
    }

    /**
     * 微信开放平台"网站应用"扫码二维码地址
     */
    protected static function qrConnectUrl(string $state): string
    {
        $redirect = urlencode(config('services.wechat.redirect_url') ?: url('/login/wechat/callback'));

        return 'https://open.weixin.qq.com/connect/qrconnect'
            .'?appid='.config('services.wechat.appid')
            .'&redirect_uri='.$redirect
            .'&response_type=code&scope=snsapi_login&state='.$state.'#wechat_redirect';
    }

    /**
     * 真实回调：用 code 换 openid（未配置 AppID 时抛异常）
     */
    public static function exchangeCode(string $code): array
    {
        if (! self::enabled()) {
            throw new \RuntimeException('未配置 WECHAT_APPID/WECHAT_SECRET，无法完成真实微信登录');
        }

        $res = Http::get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid' => config('services.wechat.appid'),
            'secret' => config('services.wechat.secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ])->json();

        if (isset($res['errcode']) && $res['errcode'] !== 0) {
            throw new \RuntimeException('微信登录失败: '.($res['errmsg'] ?? 'unknown'));
        }

        return [
            'openid' => $res['openid'],
            'unionid' => $res['unionid'] ?? null,
        ];
    }

    /**
     * 演示模式：使用固定的模拟 openid（保证同一浏览器反复扫码自动登录同一账号）
     */
    public static function mockOpenid(string $scene): string
    {
        return 'mock_demo_user_001';
    }
}
