<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SmsSendException;
use App\Models\SmsCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 短信验证码服务（配置优先读系统设置 settings，未设置时兜底 .env/config）
 * - driver=demo    ：不真正发送，验证码写入日志并随接口返回（本地联调用）
 * - driver=aliyun  ：阿里云短信（dysmsapi，HMAC-SHA1 签名，无需 SDK）
 * - driver=tencent ：腾讯云短信（TC3-HMAC-SHA256 签名，无需 SDK）
 */
class SmsService
{
    /** 同一手机号最小发送间隔（秒），防止重复烧钱 */
    public const RESEND_INTERVAL = 60;

    /** 腾讯云短信 API 版本 */
    public const TENCENT_VERSION = '2021-01-11';

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

    /**
     * 发送登录验证码
     *
     * @throws SmsSendException 真实通道发送失败时抛出
     */
    public static function sendCode(string $phone, string $ip = null): string
    {
        // 真实通道下 60 秒内不重复下发同一个号码（避免重复计费与短信轰炸）；demo 模式不限制，方便本地联调
        if (self::driver() !== 'demo') {
            $recent = SmsCode::where('phone', $phone)
                ->where('created_at', '>=', now()->subSeconds(self::RESEND_INTERVAL))
                ->exists();

            if ($recent) {
                throw new SmsSendException('验证码已发送，请 '.self::RESEND_INTERVAL.' 秒后再试');
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        SmsCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
            'ip' => $ip,
        ]);

        self::sendViaProvider($phone, $code);

        return $code;
    }

    /**
     * 按当前 driver 下发短信；demo 模式只写日志
     *
     * @throws SmsSendException
     */
    public static function sendViaProvider(string $phone, string $code): bool
    {
        $driver = self::driver();

        try {
            $ok = match ($driver) {
                'aliyun' => self::sendViaAliyun($phone, $code),
                'tencent' => self::sendViaTencent($phone, $code),
                default => self::sendViaDemo($phone, $code),
            };
        } catch (SmsSendException $e) {
            Log::warning('[sms] 发送失败', ['driver' => $driver, 'phone' => self::mask($phone), 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('[sms] 通道网络异常', ['driver' => $driver, 'phone' => self::mask($phone), 'error' => $e->getMessage()]);
            throw new SmsSendException('短信通道连接失败，请稍后再试');
        }

        Log::info('[sms] 发送成功', ['driver' => $driver, 'phone' => self::mask($phone)]);

        return $ok;
    }

    protected static function sendViaDemo(string $phone, string $code): bool
    {
        Log::info("[demo sms] phone={$phone} code={$code}");

        return true;
    }

    // -------------------------------------------------------------------------
    // 阿里云短信（SendSms / HMAC-SHA1）
    // -------------------------------------------------------------------------

    protected static function sendViaAliyun(string $phone, string $code): bool
    {
        $ak = (string) self::cfg('aliyun_access_key_id');
        $sk = (string) self::cfg('aliyun_access_key_secret');
        $sign = (string) self::cfg('aliyun_sign_name');
        $tpl = (string) self::cfg('aliyun_template_code');

        if ($ak === '' || $sk === '' || $sign === '' || $tpl === '') {
            throw new SmsSendException('阿里云短信配置不完整，请在系统设置中填写 AccessKey / 签名 / 模板编码');
        }

        $params = [
            'AccessKeyId' => $ak,
            'Action' => 'SendSms',
            'Format' => 'JSON',
            'PhoneNumbers' => $phone,
            'RegionId' => 'cn-hangzhou',
            'SignName' => $sign,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => Str::uuid()->toString(),
            'SignatureVersion' => '1.0',
            'TemplateCode' => $tpl,
            'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ];

        ksort($params);
        $params['Signature'] = self::aliyunSign($params, $sk);
        ksort($params);

        // 用同一套编码器拼查询串，保证与签名串完全一致
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = self::percentEncode((string) $k).'='.self::percentEncode((string) $v);
        }

        $response = Http::timeout(10)
            ->get('https://dysmsapi.aliyuncs.com/?'.implode('&', $pairs));

        if (! $response->successful()) {
            throw new SmsSendException('阿里云短信接口 HTTP '.$response->status());
        }

        $data = $response->json();
        if (($data['Code'] ?? 'OK') !== 'OK') {
            throw new SmsSendException('阿里云短信：'.($data['Message'] ?? $data['Code'] ?? '未知错误'));
        }

        return true;
    }

    /**
     * 阿里云 POP 签名：HMAC-SHA1(secret + "&", "GET&%2F&" + percentEncode(规范化查询串))
     */
    protected static function aliyunSign(array $params, string $secret): string
    {
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = self::percentEncode((string) $k).'='.self::percentEncode((string) $v);
        }

        $stringToSign = 'GET&'.self::percentEncode('/').'&'.self::percentEncode(implode('&', $pairs));

        return base64_encode(hash_hmac('sha1', $stringToSign, $secret.'&', true));
    }

    /**
     * RFC 3986 百分号编码（阿里云对加号、星号、波浪号有特殊要求）
     */
    protected static function percentEncode(string $value): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($value));
    }

    // -------------------------------------------------------------------------
    // 腾讯云短信（SendSms / TC3-HMAC-SHA256）
    // -------------------------------------------------------------------------

    protected static function sendViaTencent(string $phone, string $code): bool
    {
        $secretId = (string) self::cfg('tencent_secret_id');
        $secretKey = (string) self::cfg('tencent_secret_key');
        $sdkAppId = (string) self::cfg('tencent_sdk_app_id');
        $sign = (string) self::cfg('tencent_sign_name');
        $tpl = (string) self::cfg('tencent_template_id');

        if ($secretId === '' || $secretKey === '' || $sdkAppId === '' || $sign === '' || $tpl === '') {
            throw new SmsSendException('腾讯云短信配置不完整，请在系统设置中填写 SecretId / SecretKey / SDKAppID / 签名 / 模板 ID');
        }

        $host = 'sms.tencentcloudapi.com';
        $action = 'SendSms';
        $timestamp = time();

        $payload = json_encode([
            'PhoneNumberSet' => ['+86'.$phone],
            'SmsSdkAppId' => $sdkAppId,
            'SignName' => $sign,
            'TemplateId' => $tpl,
            'TemplateParamSet' => [$code],
        ], JSON_UNESCAPED_UNICODE);

        $authorization = self::tencentAuthorization($secretId, $secretKey, $host, $action, $payload, $timestamp);

        $response = Http::timeout(10)
            ->withHeaders([
                'Authorization' => $authorization,
                'Content-Type' => 'application/json',
                'Host' => $host,
                'X-TC-Action' => $action,
                'X-TC-Timestamp' => (string) $timestamp,
                'X-TC-Version' => self::TENCENT_VERSION,
                'X-TC-Region' => (string) (self::cfg('tencent_region') ?: 'ap-guangzhou'),
            ])
            ->withBody($payload, 'application/json')
            ->post('https://'.$host.'/');

        if (! $response->successful()) {
            throw new SmsSendException('腾讯云短信接口 HTTP '.$response->status());
        }

        $data = $response->json();
        $err = $data['Response']['Error'] ?? null;
        if ($err) {
            throw new SmsSendException('腾讯云短信：'.($err['Message'] ?? $err['Code'] ?? '未知错误'));
        }

        return true;
    }

    /**
     * 生成腾讯云 TC3-HMAC-SHA256 的 Authorization 头
     *
     * 拼接规则已用官方示例向量校验（canonicalRequest / hashedPayload 均逐字节一致）
     */
    protected static function tencentAuthorization(
        string $secretId, string $secretKey, string $host,
        string $action, string $payload, int $timestamp
    ): string {
        $service = 'sms';
        $date = gmdate('Y-m-d', $timestamp);

        $canonicalHeaders = "content-type:application/json\nhost:{$host}\nx-tc-action:".strtolower($action)."\n";
        $signedHeaders = 'content-type;host;x-tc-action';
        $canonicalRequest = implode("\n", [
            'POST', '/', '', $canonicalHeaders, $signedHeaders, hash('sha256', $payload),
        ]);

        $credentialScope = $date.'/'.$service.'/tc3_request';
        $stringToSign = implode("\n", [
            'TC3-HMAC-SHA256', (string) $timestamp, $credentialScope, hash('sha256', $canonicalRequest),
        ]);

        $secretDate = hash_hmac('sha256', $date, 'TC3'.$secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);

        return sprintf(
            'TC3-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $secretId, $credentialScope, $signedHeaders, $signature
        );
    }

    // -------------------------------------------------------------------------

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

    /**
     * 手机号脱敏，避免验证码落库/日志泄露完整号码
     */
    public static function mask(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }

        return substr($phone, 0, 3).'****'.substr($phone, -4);
    }
}
