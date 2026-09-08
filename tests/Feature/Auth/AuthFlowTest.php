<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => $role,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_portal_login_allows_customer(): void
    {
        $this->user('c@t.test', 'customer');

        $this->post(route('login'), ['email' => 'c@t.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_login_accepts_agent(): void
    {
        $this->user('a@t.test', 'agent');

        $this->post(route('admin.login.store'), ['account' => 'a@t.test', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard')); // 客服/管理员登录后进带前缀后台首页
    }

    public function test_admin_login_accepts_username(): void
    {
        User::factory()->create([
            'email' => 'b@t.test',
            'username' => 'boss',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        // 用用户名登录
        $this->post(route('admin.login.store'), ['account' => 'boss', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_login_rejects_customer_role(): void
    {
        $this->user('c@t.test', 'customer');

        $this->post(route('admin.login.store'), ['account' => 'c@t.test', 'password' => 'password'])
            ->assertSessionHasErrors('account');

        $this->assertGuest();
    }

    public function test_admin_login_rejects_wrong_password(): void
    {
        $this->user('a@t.test', 'agent');

        $this->post(route('admin.login.store'), ['account' => 'a@t.test', 'password' => 'wrong'])
            ->assertSessionHasErrors('account');

        $this->assertGuest();
    }
}
