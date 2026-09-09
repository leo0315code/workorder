<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KbArticle;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1 增强项测试：
 * - 登录审计：成功/失败埋点落库，管理端查看页
 * - 已关闭工单禁止回复/备注
 * - CSAT 评分时效（解决/关闭后 N 天内可评，超期拒绝）
 */
class P1EnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['setting_key' => 'work_hours_enabled'], ['value' => '0']);
    }

    private function user(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
    }

    // ---------------------------------------------------------------------
    // 登录审计
    // ---------------------------------------------------------------------

    public function test_successful_login_writes_audit(): void
    {
        $user = $this->user('customer');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('login_audits', [
            'user_id' => $user->id,
            'channel' => 'web',
            'success' => true,
        ]);
    }

    public function test_failed_login_writes_audit(): void
    {
        $user = $this->user('customer');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('login_audits', [
            'email' => $user->email,
            'channel' => 'web',
            'success' => false,
            'reason' => 'bad_credentials',
        ]);
    }

    public function test_admin_login_audit_page_lists_records(): void
    {
        $admin = $this->user('admin');
        $user = $this->user('customer');

        // 先产生一条失败 + 一条成功记录
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));

        // 管理员访问登录审计页
        $this->actingAs($admin)->get(route('admin.login-audits.index'))
            ->assertOk()
            ->assertSee('登录审计')
            ->assertSee($user->email);
    }

    // ---------------------------------------------------------------------
    // 自定义后台路径（ADMIN_URL）贯通
    // ---------------------------------------------------------------------

    public function test_agent_login_redirects_to_prefixed_dashboard(): void
    {
        $agent = $this->user('agent');

        $this->post('/login', ['email' => $agent->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        // 后台首页可访问且带自定义前缀
        $this->actingAs($agent)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame('/console', parse_url(route('admin.dashboard'), PHP_URL_PATH));
    }

    public function test_customer_login_redirects_to_public_dashboard(): void
    {
        $customer = $this->user('customer');

        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($customer)->get(route('dashboard'))->assertOk();
    }

    public function test_root_redirects_by_role(): void
    {
        $agent = $this->user('agent');
        $customer = $this->user('customer');

        $this->actingAs($agent)->get('/')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($customer)->get('/')->assertRedirect(route('dashboard'));
    }

    // ---------------------------------------------------------------------
    // 已关闭工单禁止回复
    // ---------------------------------------------------------------------

    public function test_closed_ticket_cannot_reply(): void
    {
        $customer = $this->user('customer');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.reply', $ticket), ['content' => '还能回复吗'])
            ->assertRedirect();

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'content' => '还能回复吗',
        ]);
    }

    public function test_closed_ticket_cannot_note(): void
    {
        $agent = $this->user('agent');
        $customer = $this->user('customer');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);

        $this->actingAs($agent)
            ->post(route('tickets.note', $ticket), ['content' => '内部备注'])
            ->assertRedirect();

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'type' => 'note',
        ]);
    }

    public function test_resolved_ticket_can_reply_reopens(): void
    {
        $customer = $this->user('customer');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_RESOLVED,
        ]);

        // 已解决工单允许客户补充（会重新打开）——与既有设计一致
        $this->actingAs($customer)
            ->post(route('tickets.reply', $ticket), ['content' => '补充说明'])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'content' => '补充说明',
        ]);
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // CSAT 评分时效
    // ---------------------------------------------------------------------

    public function test_rate_within_window_succeeds(): void
    {
        Setting::updateOrCreate(['setting_key' => 'csat_days'], ['value' => '7']);
        $customer = $this->user('customer');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_RESOLVED,
            'closed_at' => now()->subDays(3),
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.rate', $ticket), [
                'rating' => 5,
                'is_solved' => 1,
                'comment' => '很好',
            ])->assertRedirect();

        $this->assertDatabaseHas('ticket_ratings', [
            'ticket_id' => $ticket->id,
            'rating' => 5,
        ]);
    }

    public function test_rate_after_window_rejected(): void
    {
        Setting::updateOrCreate(['setting_key' => 'csat_days'], ['value' => '7']);
        $customer = $this->user('customer');
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_CLOSED,
            'closed_at' => now()->subDays(10),
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.rate', $ticket), [
                'rating' => 4,
                'is_solved' => 1,
            ])->assertRedirect();

        $this->assertDatabaseMissing('ticket_ratings', ['ticket_id' => $ticket->id]);
    }

    // ---------------------------------------------------------------------
    // 客户售后到期快捷调整
    // ---------------------------------------------------------------------

    public function test_warranty_extend_adds_one_year(): void
    {
        $agent = $this->user('agent');
        $customer = Customer::create([
            'company' => '测试公司',
            'contact_name' => '张三',
            'after_sales_expired_at' => now()->addYear(),
        ]);

        $this->actingAs($agent)
            ->post(route('admin.customers.warranty', $customer), ['action' => 'extend_1y'])
            ->assertRedirect();

        $this->assertSame(
            now()->addYears(2)->format('Y-m-d'),
            $customer->fresh()->after_sales_expired_at->format('Y-m-d')
        );
    }

    public function test_warranty_recalc_uses_registered_at_plus_product_warranty(): void
    {
        $agent = $this->user('agent');
        $product = Product::create(['name' => '测试产品', 'warranty_days' => 365, 'is_active' => true]);
        $customer = Customer::create([
            'company' => '测试公司2',
            'contact_name' => '李四',
            'product_id' => $product->id,
            'registered_at' => now()->subDays(100),
            'after_sales_expired_at' => null,
        ]);

        $this->actingAs($agent)
            ->post(route('admin.customers.warranty', $customer), ['action' => 'recalc'])
            ->assertRedirect();

        $this->assertSame(
            now()->subDays(100)->addDays(365)->format('Y-m-d'),
            $customer->fresh()->after_sales_expired_at->format('Y-m-d')
        );
    }

    public function test_warranty_set_custom_date(): void
    {
        $agent = $this->user('agent');
        $customer = Customer::create([
            'company' => '测试公司3',
            'contact_name' => '王五',
        ]);

        $this->actingAs($agent)
            ->post(route('admin.customers.warranty', $customer), ['action' => 'set', 'date' => '2028-06-30'])
            ->assertRedirect();

        $this->assertSame('2028-06-30', $customer->fresh()->after_sales_expired_at->format('Y-m-d'));
    }

    // ---------------------------------------------------------------------
    // SLA 临期筛选（工单列表）
    // ---------------------------------------------------------------------

    public function test_ticket_list_filters_sla_warning(): void
    {
        $agent = $this->user('agent');
        $customer = $this->user('customer');

        // 一条临期（4h 内到期）+ 一条正常
        Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_OPEN,
            'sla_due_at' => now()->addHours(3),
            'subject' => '临期工单',
        ]);
        Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_OPEN,
            'sla_due_at' => now()->addDays(3),
            'subject' => '正常工单',
        ]);

        $this->actingAs($agent)
            ->get(route('admin.tickets.index', ['warning' => 1]))
            ->assertOk()
            ->assertSee('临期工单')
            ->assertDontSee('正常工单');
    }

    // ---------------------------------------------------------------------
    // 知识库全文检索（标题 + 内容）
    // ---------------------------------------------------------------------

    public function test_kb_search_matches_content(): void
    {
        $agent = $this->user('agent');
        KbArticle::create([
            'title' => '打印机设置指南',
            'content' => '配置网络共享打印机，注意 IP 地址要固定',
            'is_published' => true,
            'created_by' => $agent->id,
        ]);
        KbArticle::create([
            'title' => '登录教程',
            'content' => '使用邮箱登录即可',
            'is_published' => true,
            'created_by' => $agent->id,
        ]);

        // 搜标题命中
        $this->actingAs($agent)
            ->get(route('admin.kb.index', ['q' => '登录']))
            ->assertOk()
            ->assertSee('登录教程')
            ->assertDontSee('打印机设置指南');

        // 搜内容命中（全文检索）
        $this->actingAs($agent)
            ->get(route('admin.kb.index', ['q' => 'IP 地址']))
            ->assertOk()
            ->assertSee('打印机设置指南')
            ->assertDontSee('登录教程');
    }

    // ---------------------------------------------------------------------
    // 报表：满意度趋势
    // ---------------------------------------------------------------------

    public function test_report_rating_daily_series_aggregates(): void
    {
        $agent = $this->user('agent');
        $customer = $this->user('customer');

        // 前两天各一条评分（5 分 / 3 分），今天一条（4 分）
        foreach ([
            [5, now()->subDays(2)],
            [3, now()->subDays(1)],
            [4, now()],
        ] as [$rating, $at]) {
            $ticket = Ticket::factory()->create([
                'user_id' => $customer->id,
                'status' => Ticket::STATUS_CLOSED,
                'closed_at' => $at,
            ]);
            $ticketRating = TicketRating::create([
                'ticket_id' => $ticket->id,
                'user_id' => $customer->id,
                'rating' => $rating,
                'is_solved' => 1,
            ]);
            // created_at 非 fillable，需 forceFill 直落库（趋势按 created_at 分组）
            $ticketRating->forceFill(['created_at' => $at])->save();
        }

        $service = new ReportService;
        [$dates, $avg, $count] = $service->ratingDailySeries($service->startOf(7));

        $this->assertCount(7, $dates);
        $this->assertSame([0, 0, 0, 0, 1, 1, 1], $count);
        // 今日 4 分（唯一一条）
        $this->assertSame(4.0, end($avg));
        $this->assertSame(1, end($count));
    }

    // ---------------------------------------------------------------------
    // 批量操作扩展：批量改优先级 / 批量指派通知
    // ---------------------------------------------------------------------

    public function test_batch_change_priority(): void
    {
        $agent = $this->user('agent');
        $customer = $this->user('customer');

        $t1 = Ticket::factory()->create(['user_id' => $customer->id, 'priority' => 'low']);
        $t2 = Ticket::factory()->create(['user_id' => $customer->id, 'priority' => 'normal']);

        $this->actingAs($agent)
            ->post(route('admin.tickets.batch'), [
                'action' => 'priority',
                'ticket_ids' => [$t1->id, $t2->id],
                'priority' => 'urgent',
            ])->assertRedirect();

        $this->assertSame('urgent', $t1->fresh()->priority);
        $this->assertSame('urgent', $t2->fresh()->priority);
    }

    public function test_batch_assign_notifies_new_assignee(): void
    {
        $agent = $this->user('agent');
        $target = $this->user('agent');
        $customer = $this->user('customer');

        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assignee_id' => null]);

        $this->actingAs($agent)
            ->post(route('admin.tickets.batch'), [
                'action' => 'assign',
                'ticket_ids' => [$ticket->id],
                'assignee_id' => $target->id,
            ])->assertRedirect();

        $this->assertSame($target->id, $ticket->fresh()->assignee_id);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $target->id,
            'title' => '工单已指派给你',
        ]);
    }

    // ---------------------------------------------------------------------
    // 仪表盘：我的待处理 / 待认领列表
    // ---------------------------------------------------------------------

    public function test_dashboard_shows_my_open_and_unassigned(): void
    {
        $agent = $this->user('agent');
        $customer = $this->user('customer');

        // 一条指派给我且超时的工单 + 一条待认领
        Ticket::factory()->create([
            'user_id' => $customer->id,
            'assignee_id' => $agent->id,
            'status' => Ticket::STATUS_OPEN,
            'sla_due_at' => now()->subHour(),
            'subject' => '我的超时工单',
        ]);
        Ticket::factory()->create([
            'user_id' => $customer->id,
            'assignee_id' => null,
            'status' => Ticket::STATUS_OPEN,
            'subject' => '待认领工单',
        ]);

        $this->actingAs($agent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('我的待处理')
            ->assertSee('我的超时工单')
            ->assertSee('待认领')
            ->assertSee('待认领工单')
            ->assertSee('超时');
    }

    // ---------------------------------------------------------------------
    // 用户级通知偏好
    // ---------------------------------------------------------------------

    public function test_notify_user_respects_sla_pref_off(): void
    {
        $agent = User::factory()->create([
            'role' => 'agent',
            'notification_prefs' => ['email' => true, 'sla' => false, 'ticket' => true],
            'password' => bcrypt('password'),
        ]);

        // SLA 偏好关闭 → 不发 SLA 类通知
        $result = NotificationService::notifyUser($agent->id, 'SLA 测试', 'body', null, 'sla');
        $this->assertNull($result);

        // ticket 偏好开启 → 正常发工单通知
        $result2 = NotificationService::notifyUser($agent->id, '工单测试', 'body', null, 'ticket');
        $this->assertNotNull($result2);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $agent->id,
            'title' => '工单测试',
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $agent->id,
            'title' => 'SLA 测试',
        ]);
    }

    public function test_notify_defaults_to_enabled_when_prefs_null(): void
    {
        $agent = User::factory()->create(['role' => 'agent', 'notification_prefs' => null]);

        $result = NotificationService::notifyUser($agent->id, '默认通知', null, null, 'sla');
        $this->assertNotNull($result);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $agent->id, 'title' => '默认通知']);
    }

    public function test_profile_update_notification_prefs(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $this->actingAs($agent)
            ->post(route('profile.notification-prefs'), [
                'prefs' => ['email'],
            ])->assertRedirect();

        $this->assertSame(
            ['email' => true, 'sla' => false, 'ticket' => false],
            $agent->fresh()->notification_prefs
        );
    }
}
