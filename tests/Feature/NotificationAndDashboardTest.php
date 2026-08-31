<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NotificationAndDashboardTest：通知中心（归属隔离/已读越权/全部已读）与仪表盘（客户/客服/recent scope/禁缓存）、SLA due_at、CSAT、回复通知链路
 */
class NotificationAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['setting_key' => 'work_hours_enabled', 'value' => '0']);
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function user(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
    }

    // -------------------------------------------------------------------------
    // 通知中心
    // -------------------------------------------------------------------------

    public function test_user_sees_only_own_notifications(): void
    {
        $me = $this->user();
        $other = $this->user();
        UserNotification::create(['user_id' => $me->id, 'title' => '我的通知', 'is_read' => false]);
        UserNotification::create(['user_id' => $other->id, 'title' => '别人的通知', 'is_read' => false]);

        $response = $this->actingAs($me)->get(route('notifications.index'));

        $response->assertOk()->assertSee('我的通知')->assertDontSee('别人的通知');
    }

    public function test_unread_count_and_latest_are_scoped_to_user(): void
    {
        $me = $this->user();
        UserNotification::create(['user_id' => $me->id, 'title' => '未读一', 'is_read' => false]);
        UserNotification::create(['user_id' => $me->id, 'title' => '未读二', 'is_read' => false]);
        UserNotification::create(['user_id' => $me->id, 'title' => '已读', 'is_read' => true]);

        $this->actingAs($me)->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 2]);

        $this->actingAs($me)->getJson(route('notifications.latest'))
            ->assertOk()
            ->assertJsonCount(3, 'items');
    }

    public function test_mark_read_only_own_notification(): void
    {
        $me = $this->user();
        $other = $this->user();
        $mine = UserNotification::create(['user_id' => $me->id, 'title' => '我的', 'is_read' => false]);
        $theirs = UserNotification::create(['user_id' => $other->id, 'title' => '他的', 'is_read' => false]);

        // 标记自己的 → 重定向到 link
        $this->actingAs($me)
            ->post(route('notifications.read', $mine))
            ->assertRedirect(route('notifications.index'));
        $this->assertTrue($mine->fresh()->is_read);

        // 标记别人的 → 403
        $this->actingAs($me)
            ->post(route('notifications.read', $theirs))
            ->assertForbidden();
        $this->assertFalse($theirs->fresh()->is_read);
    }

    public function test_mark_all_read(): void
    {
        $me = $this->user();
        UserNotification::create(['user_id' => $me->id, 'title' => 'a', 'is_read' => false]);
        UserNotification::create(['user_id' => $me->id, 'title' => 'b', 'is_read' => false]);

        $this->actingAs($me)
            ->post(route('notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, UserNotification::where('user_id', $me->id)->unread()->count());
    }

    // -------------------------------------------------------------------------
    // 仪表盘
    // -------------------------------------------------------------------------

    public function test_dashboard_renders_for_customer(): void
    {
        $this->actingAs($this->user('customer'))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_renders_for_agent(): void
    {
        $this->actingAs($this->user('agent'))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_recent_fragment_supports_scopes(): void
    {
        Ticket::factory()->create(['status' => Ticket::STATUS_OPEN, 'subject' => '待处理单']);
        Ticket::factory()->create(['status' => Ticket::STATUS_RESOLVED, 'subject' => '已解决单']);

        $agent = $this->user('agent');

        $all = $this->actingAs($agent)->get(route('dashboard.recent', ['scope' => 'all']))->assertOk();
        $this->assertStringContainsString('待处理单', $all->getContent());
        $this->assertStringContainsString('已解决单', $all->getContent());

        $open = $this->actingAs($agent)->get(route('dashboard.recent', ['scope' => 'open']))->assertOk();
        $this->assertStringContainsString('待处理单', $open->getContent());
        $this->assertStringNotContainsString('已解决单', $open->getContent());

        // 非法 scope 回退 all
        $this->actingAs($agent)->get(route('dashboard.recent', ['scope' => 'hack']))->assertOk();

        // 片段接口禁缓存
        $this->assertStringContainsString('no-store', (string) $all->headers->get('Cache-Control'));
    }

    // -------------------------------------------------------------------------
    // SLA 与 CSAT 链路
    // -------------------------------------------------------------------------

    public function test_ticket_sla_due_at_is_set_on_create(): void
    {
        Setting::create(['setting_key' => 'sla_normal', 'value' => '48']);

        $this->actingAs($this->user('customer'))
            ->post(route('tickets.store'), [
                'subject' => 'SLA 测试',
                'description' => '描述',
                'priority' => 'normal',
            ])
            ->assertRedirect();

        $ticket = Ticket::where('subject', 'SLA 测试')->firstOrFail();
        $this->assertNotNull($ticket->sla_due_at);
        $this->assertGreaterThan(now()->addHours(47), $ticket->sla_due_at);
        $this->assertLessThanOrEqual(now()->addHours(49), $ticket->sla_due_at);
    }

    public function test_csat_rating_creates_record(): void
    {
        $customer = $this->user('customer');
        $agent = $this->user('agent');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_RESOLVED,
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.rate', $ticket), [
                'rating' => 5,
                'is_solved' => 1,
                'comment' => '客服很专业',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_ratings', [
            'ticket_id' => $ticket->id,
            'rating' => 5,
            'is_solved' => 1,
        ]);
    }

    public function test_csat_rating_requires_resolved_status(): void
    {
        $customer = $this->user('customer');
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($customer)
            ->post(route('tickets.rate', $ticket), ['rating' => 5, 'is_solved' => 1])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ticket_ratings', 0);
    }

    public function test_reply_creates_log_and_notification(): void
    {
        $customer = $this->user('customer');
        $agent = $this->user('agent');
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($agent)
            ->post(route('tickets.reply', $ticket), ['content' => '我们正在处理中'])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'type' => TicketReply::TYPE_REPLY,
            'content' => '我们正在处理中',
        ]);

        // 客户收到站内通知
        $this->assertTrue(
            UserNotification::where('user_id', $customer->id)->where('title', 'like', '%新回复%')->exists()
        );
    }
}
