<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
    }

    private function aliyunConfig(): void
    {
        Setting::create(['setting_key' => 'sms_driver', 'value' => 'aliyun']);
        foreach ([
            'sms_aliyun_access_key_id' => 'LTAI-test',
            'sms_aliyun_access_key_secret' => 'secret-test',
            'sms_aliyun_sign_name' => 'TestSign',
            'sms_aliyun_template_code' => 'SMS_123456',
        ] as $key => $value) {
            Setting::create(['setting_key' => $key, 'value' => $value]);
        }
    }

    public function test_non_admin_cannot_send_test_sms(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $this->actingAs($agent)
            ->post(route('admin.settings.sms-test'), ['sms_test_phone' => '13800138000'])
            ->assertForbidden();
    }

    public function test_test_sms_requires_valid_phone(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms-test'), ['sms_test_phone' => '12345'])
            ->assertSessionHasErrors('sms_test_phone');
    }

    public function test_test_sms_reports_success(): void
    {
        Http::fake(['dysmsapi.aliyuncs.com/*' => Http::response(['Code' => 'OK'], 200)]);
        $this->aliyunConfig();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms-test'), ['sms_test_phone' => '13800138000'])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'PhoneNumbers=13800138000'));
    }

    public function test_test_sms_does_not_write_sms_codes(): void
    {
        Http::fake(['dysmsapi.aliyuncs.com/*' => Http::response(['Code' => 'OK'], 200)]);
        $this->aliyunConfig();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms-test'), ['sms_test_phone' => '13800138000']);

        $this->assertDatabaseCount('sms_codes', 0);
    }

    public function test_test_sms_reports_provider_error(): void
    {
        Http::fake([
            'dysmsapi.aliyuncs.com/*' => Http::response([
                'Code' => 'isv.SMS_TEMPLATE_ILLEGAL', 'Message' => '模板不合法',
            ], 200),
        ]);
        $this->aliyunConfig();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms-test'), ['sms_test_phone' => '13800138000'])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('error', '发送失败：阿里云短信：模板不合法');
    }

    public function test_test_sms_warns_under_demo_driver(): void
    {
        Http::fake();
        Setting::create(['setting_key' => 'sms_driver', 'value' => 'demo']);

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms-test'), ['sms_test_phone' => '13800138000'])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('success');

        Http::assertNothingSent();
    }

    public function test_settings_page_renders_sms_test_control(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('发送测试短信')
            ->assertSee(route('admin.settings.sms-test'), false);
    }
}
