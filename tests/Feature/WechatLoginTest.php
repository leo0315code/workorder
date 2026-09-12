<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WechatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WechatLoginTest：微信扫码登录链路（QR 创建 / 状态轮询 / 模拟扫码 / 绑定已有账号 / 注册新账号 / 真实回调）
 * - 覆盖演示模式（未配置 AppID）完整链路：扫码 → 未绑定 → 绑定/注册 → 登录
 * - 覆盖真实回调 exchangeCode 的 HTTP mock
 * - 认证安全面：密码错误记 bad_credentials 审计、会话过期拒绝绑定
 */
class WechatLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function newScene(): string
    {
        return WechatService::createSession()['scene'];
    }

    private function makeScannedUser(?string $openid = null): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'wechat_openid' => $openid ?? WechatService::mockOpenid('x'),
        ]);
    }

    // -------------------------------------------------------------------------
    // QR 会话创建
    // -------------------------------------------------------------------------

    public function test_qr_creates_session_with_demo_mode(): void
    {
        $res = $this->getJson(route('login.wechat.qr'))->assertOk()->json();

        $this->assertArrayHasKey('scene', $res);
        $this->assertNull($res['qr_url']); // 未配置 AppID → 演示模式无真实二维码

        // 会话已写入缓存
        $this->assertNotNull(WechatService::getState($res['scene']));
    }

    // -------------------------------------------------------------------------
    // 状态轮询
    // -------------------------------------------------------------------------

    public function test_status_returns_expired_for_unknown_scene(): void
    {
        $this->getJson(route('login.wechat.status', 'no-such-scene'))
            ->assertOk()
            ->assertJson(['status' => 'expired']);
    }

    public function test_status_pending_before_scan(): void
    {
        $scene = $this->newScene();

        $this->getJson(route('login.wechat.status', $scene))
            ->assertOk()
            ->assertJson(['status' => 'pending']);
    }

    public function test_status_auto_logs_in_bound_user(): void
    {
        $user = $this->makeScannedUser();
        $scene = $this->newScene();
        $this->postJson(route('login.wechat.mock', $scene))->assertOk();

        // 已绑定 openid → 轮询直接登录并返回跳转
        $this->getJson(route('login.wechat.status', $scene))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertAuthenticatedAs($user);
    }

    public function test_status_returns_need_bind_for_unbound_user(): void
    {
        $this->makeScannedUser('some-other-openid');
        $scene = $this->newScene();
        $this->postJson(route('login.wechat.mock', $scene))->assertOk();

        $this->getJson(route('login.wechat.status', $scene))
            ->assertOk()
            ->assertJson(['status' => 'need_bind']);
    }

    // -------------------------------------------------------------------------
    // 模拟扫码
    // -------------------------------------------------------------------------

    public function test_mock_returns_404_for_expired_scene(): void
    {
        $this->postJson(route('login.wechat.mock', 'no-such-scene'))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // 绑定已有账号
    // -------------------------------------------------------------------------

    public function test_bind_existing_account(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email' => 'bind@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $scene = $this->newScene();
        $this->postJson(route('login.wechat.mock', $scene))->assertOk();

        $this->post(route('login.wechat.bind-store', $scene), [
            'mode' => 'bind',
            'email' => 'bind@test.com',
            'password' => 'secret123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(WechatService::mockOpenid($scene), $user->fresh()->wechat_openid);
    }

    public function test_bind_existing_account_wrong_password_logs_audit(): void
    {
        User::factory()->create([
            'role' => 'customer',
            'email' => 'bind@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $scene = $this->newScene();
        $this->postJson(route('login.wechat.mock', $scene))->assertOk();

        $this->post(route('login.wechat.bind-store', $scene), [
            'mode' => 'bind',
            'email' => 'bind@test.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('login_audits', [
            'channel' => 'wechat',
            'email' => 'bind@test.com',
            'success' => 0,
            'reason' => 'bad_credentials',
        ]);
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // 注册新账号
    // -------------------------------------------------------------------------

    public function test_bind_register_new_account(): void
    {
        $scene = $this->newScene();
        $this->postJson(route('login.wechat.mock', $scene))->assertOk();

        $this->post(route('login.wechat.bind-store', $scene), [
            'mode' => 'register',
            'name' => '微信新用户',
            'phone' => '',
        ])->assertRedirect();

        $user = User::where('wechat_openid', WechatService::mockOpenid($scene))->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertSame('微信新用户', $user->name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_bind_register_links_existing_phone(): void
    {
        $existing = User::factory()->create([
            'role' => 'customer',
            'phone' => '13800138000',
        ]);

        $scene = $this->newScene();
        $this->postJson(route('login.wechat.mock', $scene))->assertOk();

        $this->post(route('login.wechat.bind-store', $scene), [
            'mode' => 'register',
            'name' => '同名用户',
            'phone' => '13800138000',
        ])->assertRedirect();

        // 复用已有账号，不新建
        $this->assertSame(WechatService::mockOpenid($scene), $existing->fresh()->wechat_openid);
        $this->assertSame(1, User::where('phone', '13800138000')->count());
    }

    // -------------------------------------------------------------------------
    // 真实回调
    // -------------------------------------------------------------------------

    public function test_callback_rejects_missing_params(): void
    {
        $this->get(route('login.wechat.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_callback_exchanges_code_and_redirects_to_bind(): void
    {
        config(['services.wechat.appid' => 'wx-test', 'services.wechat.secret' => 's3cret']);

        Http::fake([
            'https://api.weixin.qq.com/sns/oauth2/access_token*' => Http::response([
                'openid' => 'wx-openid-1',
                'unionid' => 'wx-union-1',
            ]),
        ]);

        $scene = $this->newScene();

        $this->get(route('login.wechat.callback', ['code' => 'authcode', 'state' => $scene]))
            ->assertRedirect(route('login.wechat.bind', $scene));

        $state = WechatService::getState($scene);
        $this->assertSame('ready', $state['status']);
        $this->assertSame('wx-openid-1', $state['openid']);
    }

    public function test_callback_handles_wechat_error(): void
    {
        config(['services.wechat.appid' => 'wx-test', 'services.wechat.secret' => 's3cret']);

        Http::fake([
            'https://api.weixin.qq.com/sns/oauth2/access_token*' => Http::response([
                'errcode' => 40029,
                'errmsg' => 'invalid code',
            ]),
        ]);

        $scene = $this->newScene();

        $this->get(route('login.wechat.callback', ['code' => 'bad', 'state' => $scene]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    // -------------------------------------------------------------------------
    // 绑定页鉴权
    // -------------------------------------------------------------------------

    public function test_bind_page_aborts_without_ready_state(): void
    {
        $this->get(route('login.wechat.bind', 'no-such-scene'))
            ->assertStatus(419);
    }
}
