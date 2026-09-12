<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_email_uses_server_side_value_not_alpine_expression(): void
    {
        // 回归：登录页 email 字段曾用 :value="old('email')"（Alpine 属性），
        // Blade 不处理冒号属性 → 渲染为裸 old('email') → Alpine 报 old is not defined
        $html = $this->get('/login')->assertOk()->getContent();

        // email 输入框必须是服务端渲染的 value（而非 Alpine :value 绑定）
        $this->assertMatchesRegularExpression('/name="email"[^>]*value="/', $html);
        // 渲染产物中不得出现裸 old() JS 表达式
        $this->assertStringNotContainsString(':value="old(', $html);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_users_can_authenticate_with_username(): void
    {
        // 前台登录支持用户名（UserFactory 默认生成 username）
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_wrong_username(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => '不存在的用户名',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
