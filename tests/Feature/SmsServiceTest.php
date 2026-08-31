<?php

namespace Tests\Feature;

use App\Exceptions\SmsSendException;
use App\Models\Setting;
use App\Models\SmsCode;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configure(string $driver, array $extra = []): void
    {
        Setting::create(['setting_key' => 'sms_driver', 'value' => $driver]);
        foreach ($extra as $key => $value) {
            Setting::create(['setting_key' => 'sms_'.$key, 'value' => $value]);
        }
    }

    private function aliyunConfig(): array
    {
        return [
            'aliyun_access_key_id' => 'LTAI-test',
            'aliyun_access_key_secret' => 'secret-test',
            'aliyun_sign_name' => 'TestSign',
            'aliyun_template_code' => 'SMS_123456',
        ];
    }

    private function tencentConfig(): array
    {
        return [
            'tencent_secret_id' => 'AKIDtest',
            'tencent_secret_key' => 'secret-test',
            'tencent_sdk_app_id' => '1400000000',
            'tencent_sign_name' => 'TestSign',
            'tencent_template_id' => '1234567',
        ];
    }

    public function test_demo_driver_does_not_call_provider(): void
    {
        Http::fake();
        $this->configure('demo');

        $code = SmsService::sendCode('13800138000', '127.0.0.1');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertDatabaseHas('sms_codes', ['phone' => '13800138000', 'code' => $code]);
        Http::assertNothingSent();
    }

    public function test_demo_driver_allows_immediate_resend(): void
    {
        $this->configure('demo');

        SmsService::sendCode('13800138000');
        SmsService::sendCode('13800138000');

        $this->assertSame(2, SmsCode::where('phone', '13800138000')->count());
    }

    public function test_aliyun_sends_signed_request(): void
    {
        Http::fake([
            'dysmsapi.aliyuncs.com/*' => Http::response(['Code' => 'OK', 'Message' => 'OK'], 200),
        ]);
        $this->configure('aliyun', $this->aliyunConfig());

        $code = SmsService::sendCode('13800138000', '127.0.0.1');

        Http::assertSent(function ($request) use ($code) {
            $url = urldecode($request->url());

            return $request->method() === 'GET'
                && str_contains($url, 'Action=SendSms')
                && str_contains($url, 'PhoneNumbers=13800138000')
                && str_contains($url, 'SignName=TestSign')
                && str_contains($url, 'TemplateCode=SMS_123456')
                && str_contains($url, '"code":"'.$code.'"')
                && str_contains($url, 'Signature=')
                && str_contains($url, 'SignatureMethod=HMAC-SHA1');
        });
    }

    public function test_aliyun_throws_on_business_error(): void
    {
        Http::fake([
            'dysmsapi.aliyuncs.com/*' => Http::response([
                'Code' => 'isv.BUSINESS_LIMIT_CONTROL',
                'Message' => '触发小时级流控',
            ], 200),
        ]);
        $this->configure('aliyun', $this->aliyunConfig());

        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('触发小时级流控');

        SmsService::sendCode('13800138000');
    }

    public function test_aliyun_throws_when_config_incomplete(): void
    {
        Http::fake();
        $this->configure('aliyun', ['aliyun_access_key_id' => 'LTAI-test']);

        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('阿里云短信配置不完整');

        SmsService::sendCode('13800138000');
        Http::assertNothingSent();
    }

    public function test_tencent_sends_tc3_signed_request(): void
    {
        Http::fake([
            'sms.tencentcloudapi.com/*' => Http::response([
                'Response' => ['SendStatusSet' => [['Code' => 'Ok']], 'RequestId' => 'req-1'],
            ], 200),
        ]);
        $this->configure('tencent', $this->tencentConfig());

        $code = SmsService::sendCode('13800138000', '127.0.0.1');

        Http::assertSent(function ($request) use ($code) {
            $body = (string) $request->body();

            return $request->method() === 'POST'
                && $request->hasHeader('X-TC-Action', 'SendSms')
                && str_contains((string) $request->header('Authorization')[0], 'TC3-HMAC-SHA256 Credential=AKIDtest/')
                && str_contains($body, '+8613800138000')
                && str_contains($body, '"SmsSdkAppId":"1400000000"')
                && str_contains($body, '"'.$code.'"');
        });
    }

    public function test_tencent_throws_on_error_response(): void
    {
        Http::fake([
            'sms.tencentcloudapi.com/*' => Http::response([
                'Response' => ['Error' => ['Code' => 'LimitExceeded.PhoneNumberThirtySecondLimit', 'Message' => '单号码发送超限'], 'RequestId' => 'req-2'],
            ], 200),
        ]);
        $this->configure('tencent', $this->tencentConfig());

        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('单号码发送超限');

        SmsService::sendCode('13800138000');
    }

    public function test_tencent_throws_when_config_incomplete(): void
    {
        Http::fake();
        $this->configure('tencent', ['tencent_secret_id' => 'AKIDtest']);

        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('腾讯云短信配置不完整');

        SmsService::sendCode('13800138000');
        Http::assertNothingSent();
    }

    public function test_network_failure_is_wrapped(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connection reset');
        });
        $this->configure('aliyun', $this->aliyunConfig());

        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('短信通道连接失败，请稍后再试');

        SmsService::sendCode('13800138000');
    }

    public function test_real_driver_blocks_resend_within_interval(): void
    {
        Http::fake(['dysmsapi.aliyuncs.com/*' => Http::response(['Code' => 'OK'], 200)]);
        $this->configure('aliyun', $this->aliyunConfig());

        SmsService::sendCode('13800138000');

        $this->expectException(SmsSendException::class);
        $this->expectExceptionMessage('60 秒后再试');

        SmsService::sendCode('13800138000');
    }

    public function test_real_driver_allows_resend_after_interval(): void
    {
        Http::fake(['dysmsapi.aliyuncs.com/*' => Http::response(['Code' => 'OK'], 200)]);
        $this->configure('aliyun', $this->aliyunConfig());

        SmsService::sendCode('13800138000');
        $this->travel(61)->seconds();
        SmsService::sendCode('13800138000');

        $this->assertSame(2, SmsCode::where('phone', '13800138000')->count());
    }

    public function test_send_code_endpoint_reports_provider_failure(): void
    {
        Http::fake([
            'dysmsapi.aliyuncs.com/*' => Http::response(['Code' => 'isv.SMS_SIGNATURE_ILLEGAL', 'Message' => '签名不合法'], 200),
        ]);
        $this->configure('aliyun', $this->aliyunConfig());

        $this->postJson(route('login.phone.send-code'), ['phone' => '13800138000'])
            ->assertStatus(502)
            ->assertJsonPath('message', '阿里云短信：签名不合法');
    }

    public function test_phone_mask_hides_middle_digits(): void
    {
        $this->assertSame('138****8000', SmsService::mask('13800138000'));
    }

    /**
     * 用阿里云官方签名向量（AccessKeyId=testid / AccessKeySecret=testsecret）校验实现，
     * 防止后续改动悄悄把签名算法改坏（线上会表现为所有短信静默失败）
     */
    public function test_aliyun_signature_matches_official_vector(): void
    {
        $params = [
            'Timestamp' => '2016-02-23T12:46:24Z',
            'Format' => 'XML',
            'AccessKeyId' => 'testid',
            'Action' => 'DescribeRegions',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => '3ee8c1b8-83d3-44af-a94f-4e0ad82fd6cf',
            'Version' => '2014-05-26',
            'SignatureVersion' => '1.0',
        ];
        ksort($params);

        $sign = (new \ReflectionMethod(SmsService::class, 'aliyunSign'));
        $sign->setAccessible(true);

        $this->assertSame(
            'OLeaidS1JvxuMvnyHOwuJ+uX5qY=',
            $sign->invoke(null, $params, 'testsecret')
        );
    }

    /**
     * 腾讯云 TC3 签名回归向量：canonicalRequest / hashedPayload 与官方示例字节一致，
     * 最终 signature 由独立参考实现交叉验证。改动签名时必须同步更新此向量。
     */
    public function test_tencent_signature_matches_reference_vector(): void
    {
        $payload = json_encode([
            'PhoneNumberSet' => ['+8613800138000'],
            'SmsSdkAppId' => '1400000000',
            'SignName' => 'TestSign',
            'TemplateId' => '1234567',
            'TemplateParamSet' => ['123456'],
        ], JSON_UNESCAPED_UNICODE);

        $method = new \ReflectionMethod(SmsService::class, 'tencentAuthorization');
        $method->setAccessible(true);

        $this->assertSame(
            'TC3-HMAC-SHA256 Credential=AKIDTESTSECRETID/2019-02-25/sms/tc3_request, '
            .'SignedHeaders=content-type;host;x-tc-action, '
            .'Signature=152160074e69513b0756566c0017cc8a6b6502feb7a6902813b6969f2456c17b',
            $method->invoke(null, 'AKIDTESTSECRETID', 'TESTSECRETKEY', 'sms.tencentcloudapi.com', 'SendSms', $payload, 1551113065)
        );
    }

    public function test_percent_encode_follows_rfc3986(): void
    {
        $encode = (new \ReflectionMethod(SmsService::class, 'percentEncode'));
        $encode->setAccessible(true);

        // 空格走 %20 而不是 +；星号编码；波浪号不编码
        $this->assertSame('a%20b%2Ac~d', $encode->invoke(null, 'a b*c~d'));
    }
}
