<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_present_on_all_responses(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_trusted_proxy_honors_client_ip(): void
    {
        // 默认信任 127.0.0.1（本机 nginx）：伪造 X-Forwarded-For 后登录审计应记录真实客户端 IP
        $user = User::factory()->create(['role' => 'customer']);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ], ['X-Forwarded-For' => '203.0.113.7']);

        $this->assertDatabaseHas('login_audits', [
            'email' => $user->email,
            'success' => 0,
            'ip' => '203.0.113.7',
        ]);
    }
}
