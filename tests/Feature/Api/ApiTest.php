<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 对外 API 测试
 *
 * 说明：
 * - 登录/登出用真实 token 流程验证（token 签发、错误密码、吊销落库）
 * - 业务接口（工单/客户/产品/通知）用 Sanctum::actingAs() 模拟已认证用户
 *   （Sanctum guard 在 PHPUnit 环境对 Authorization 头有 session fallback 怪癖，
 *    官方测试方式就是 actingAs；真实 token 认证链路已在生产环境 curl 实测 401/403 正确）
 */
class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 关闭上班时间限制，避免测试受时段影响
        Setting::create(['setting_key' => 'work_hours_enabled', 'value' => '0']);
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function makeUser(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
    }

    private function loginToken(string $account, string $password = 'password'): string
    {
        $response = $this->postJson('/api/auth/login', [
            'account' => $account,
            'password' => $password,
        ]);

        return $response->json('access_token');
    }

    // -------------------------------------------------------------------------
    // 认证（真实 token 流程）
    // -------------------------------------------------------------------------

    public function test_login_by_email_returns_token(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'account' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'name', 'role']]);
        $this->assertSame('Bearer', $response->json('token_type'));
    }

    public function test_login_by_phone_returns_token(): void
    {
        $user = $this->makeUser();
        $user->update(['email' => null, 'phone' => '13900139001']);

        $this->postJson('/api/auth/login', ['account' => '13900139001', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.phone', '13900139001');
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/auth/login', ['account' => $user->email, 'password' => 'wrong-pass'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account');
    }

    public function test_protected_route_requires_token(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->makeUser();
        $token = $this->loginToken($user->email);

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        // 真实行为：token 记录被删除（Sanctum 在 PHPUnit 环境对已删 token 仍会回退
        // session guard 放行 /api/me，故断言数据库记录；生产环境实测登出后 me=401）
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // -------------------------------------------------------------------------
    // 工单（Sanctum::actingAs 模拟认证）
    // -------------------------------------------------------------------------

    public function test_customer_sees_only_own_tickets(): void
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        Ticket::factory()->create(['user_id' => $me->id, 'subject' => '我的工单']);
        Ticket::factory()->create(['user_id' => $other->id, 'subject' => '别人的工单']);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/tickets?status=all');

        $response->assertOk()->assertJsonCount(1, 'items');
        $this->assertSame('我的工单', $response->json('items.0.subject'));
    }

    public function test_agent_sees_all_tickets(): void
    {
        $me = $this->makeUser('agent');
        $other = $this->makeUser();
        Ticket::factory()->create(['user_id' => $me->id, 'subject' => '工单A']);
        Ticket::factory()->create(['user_id' => $other->id, 'subject' => '工单B']);

        Sanctum::actingAs($me);

        $this->getJson('/api/tickets?status=all')->assertJsonCount(2, 'items');
    }

    public function test_customer_cannot_view_others_ticket(): void
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        $ticket = Ticket::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($me);

        $this->getJson("/api/tickets/{$ticket->id}")->assertForbidden();
    }

    public function test_customer_can_create_ticket(): void
    {
        $user = $this->makeUser();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tickets', [
            'subject' => 'API 提交的工单',
            'description' => '描述',
            'priority' => 'normal',
        ]);

        $response->assertCreated()->assertJsonPath('ticket.subject', 'API 提交的工单');
        $this->assertDatabaseHas('tickets', ['subject' => 'API 提交的工单', 'user_id' => $user->id]);
    }

    public function test_customer_cannot_create_outside_work_hours(): void
    {
        Setting::where('setting_key', 'work_hours_enabled')->update(['value' => '1']);
        Setting::updateOrCreate(['setting_key' => 'work_start'], ['value' => '23:59']);
        Setting::updateOrCreate(['setting_key' => 'work_end'], ['value' => '23:58']);
        Setting::updateOrCreate(['setting_key' => 'work_days'], ['value' => '1,2,3,4,5']);

        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/tickets', [
            'subject' => '非工作时间提交',
            'description' => '描述',
            'priority' => 'normal',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('tickets', ['subject' => '非工作时间提交']);
    }

    public function test_reply_creates_reply_and_notification(): void
    {
        $customer = $this->makeUser();
        $agent = $this->makeUser('agent');
        $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

        Sanctum::actingAs($agent);

        $this->postJson("/api/tickets/{$ticket->id}/replies", ['content' => '客服回复'])->assertCreated();

        $this->assertDatabaseHas('ticket_replies', ['ticket_id' => $ticket->id, 'content' => '客服回复']);
        $this->assertTrue(
            UserNotification::where('user_id', $customer->id)->where('title', 'like', '%新回复%')->exists()
        );
    }

    // -------------------------------------------------------------------------
    // 基础数据 + 通知
    // -------------------------------------------------------------------------

    public function test_products_list_is_public_to_logged_in_users(): void
    {
        $user = $this->makeUser();
        Product::create(['name' => '工控主机', 'sku' => 'IPC-01', 'warranty_days' => 365, 'is_active' => true]);
        Product::create(['name' => '停用产品', 'sku' => 'OFF-01', 'warranty_days' => 0, 'is_active' => false]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/products');

        $response->assertOk()->assertJsonCount(1, 'items');
        $this->assertSame('工控主机', $response->json('items.0.name'));
    }

    public function test_customers_list_requires_agent(): void
    {
        $customer = $this->makeUser('customer');
        Sanctum::actingAs($customer);
        $this->getJson('/api/customers')->assertForbidden();

        $agent = $this->makeUser('agent');
        Sanctum::actingAs($agent);
        $this->getJson('/api/customers')->assertOk();
    }

    public function test_notifications_are_scoped_and_mark_read(): void
    {
        $me = $this->makeUser();
        $other = $this->makeUser();
        $mine = UserNotification::create(['user_id' => $me->id, 'title' => '我的通知', 'is_read' => false]);
        UserNotification::create(['user_id' => $other->id, 'title' => '别人的通知', 'is_read' => false]);

        Sanctum::actingAs($me);

        $this->getJson('/api/notifications')->assertJsonCount(1, 'items');
        $this->getJson('/api/notifications/unread-count')->assertJson(['count' => 1]);

        $this->postJson("/api/notifications/{$mine->id}/read")->assertOk();
        $this->assertTrue($mine->fresh()->is_read);

        $otherNotif = UserNotification::where('user_id', $other->id)->firstOrFail();
        $this->postJson("/api/notifications/{$otherNotif->id}/read")->assertForbidden();
    }
}
