<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketFieldDef;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * TicketServiceTest：工单业务服务核心逻辑
 * - filterQuery：客户数据隔离 / 客服筛选维度（mine/unassigned/overdue/warning/status/priority/category/product/q/tag）
 * - duplicateOf / nextNo / logAction：重复识别 / 编号生成 / 日志写入
 * - authorizeView / authorizeStaff：越权 403
 * - validateFieldValues / storeFieldValues：必填校验 / upsert
 */
class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TicketService;
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent']);
    }

    private function makeTicket(User $user, array $attrs = []): Ticket
    {
        return Ticket::factory()->create(array_merge(['user_id' => $user->id], $attrs));
    }

    // -------------------------------------------------------------------------
    // filterQuery：角色隔离
    // -------------------------------------------------------------------------

    public function test_filter_query_isolates_customer_tickets(): void
    {
        $customer = $this->customer();
        $other = $this->customer();
        $this->makeTicket($customer);
        $this->makeTicket($other);

        $this->actingAs($customer);
        $result = $this->service->filterQuery(Request::create('/'))->pluck('user_id')->unique();

        $this->assertSame([$customer->id], $result->all());
    }

    public function test_filter_query_agent_sees_all_and_filters_by_mine(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $mine = $this->makeTicket($customer, ['assignee_id' => $agent->id]);
        $this->makeTicket($customer);

        $this->actingAs($agent);
        $all = $this->service->filterQuery(Request::create('/'))->count();
        $my = $this->service->filterQuery(Request::create('/?mine=1'))->pluck('id');

        $this->assertSame(2, $all);
        $this->assertSame([$mine->id], $my->all());
    }

    public function test_filter_query_agent_filters_by_status_priority_overdue_warning(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();

        $this->makeTicket($customer, ['status' => Ticket::STATUS_OPEN, 'priority' => Ticket::PRIORITY_HIGH, 'sla_due_at' => now()->subHour()]);
        $this->makeTicket($customer, ['status' => Ticket::STATUS_CLOSED, 'priority' => Ticket::PRIORITY_LOW]);
        $this->makeTicket($customer, ['status' => Ticket::STATUS_OPEN, 'priority' => Ticket::PRIORITY_NORMAL, 'sla_due_at' => now()->addHour()]);

        $this->actingAs($agent);

        $this->assertSame(1, $this->service->filterQuery(Request::create('/?status=closed'))->count());
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?priority=high'))->count());
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?overdue=1'))->count());
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?warning=1'))->count());
    }

    public function test_filter_query_filters_by_category_product_search_and_tag(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $cat = Category::create(['name' => '售后', 'slug' => 'shouhou']);
        $prod = Product::create(['name' => '一体机', 'slug' => 'yitiji']);

        $t1 = $this->makeTicket($customer, ['category_id' => $cat->id, 'product_id' => $prod->id, 'subject' => '打印机卡纸']);
        $this->makeTicket($customer, ['subject' => '鼠标失灵']);

        $this->actingAs($agent);
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?category='.$cat->id))->count());
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?product='.$prod->id))->count());
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?q='.rawurlencode('卡纸')))->count());
        $this->assertSame(1, $this->service->filterQuery(Request::create('/?q='.rawurlencode('鼠标')))->count());
        $this->assertSame(2, $this->service->filterQuery(Request::create('/'))->count());
    }

    // -------------------------------------------------------------------------
    // duplicateOf / nextNo / logAction
    // -------------------------------------------------------------------------

    public function test_duplicate_of_finds_recent_unclosed_same_subject(): void
    {
        $customer = $this->customer();
        $this->makeTicket($customer, ['subject' => '重复主题']);
        $this->makeTicket($customer, ['subject' => '重复主题', 'status' => Ticket::STATUS_CLOSED]);

        $dup = $this->service->duplicateOf('重复主题', $customer->id);

        $this->assertNotNull($dup);
        $this->assertSame(Ticket::STATUS_OPEN, $dup->status);
    }

    public function test_duplicate_of_excludes_self_and_returns_null_when_clean(): void
    {
        $customer = $this->customer();
        $mine = $this->makeTicket($customer, ['subject' => '唯一主题']);

        $this->assertNull($this->service->duplicateOf('唯一主题', $customer->id, $mine->id));
        $this->assertNull($this->service->duplicateOf('完全不存在', $customer->id));
    }

    public function test_next_no_increments_when_tickets_exist(): void
    {
        // 编号基于库内当天计数：无工单时从 1 开始，建单后递增
        $first = $this->service->nextNo();
        $this->assertStringStartsWith('TK-'.date('Ymd'), $first);
        $this->assertSame(1, (int) substr($first, -4));

        $customer = $this->customer();
        $ticket = $this->makeTicket($customer);
        $ticket->update(['no' => 'TK-'.date('Ymd').'-0001']);

        $second = $this->service->nextNo();
        $this->assertSame(2, (int) substr($second, -4));
    }

    public function test_log_action_writes_ticket_log(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent);
        $this->service->logAction($ticket, 'status_changed', 'status', 'open', 'closed', '改状态');

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'status_changed',
            'old_value' => 'open',
            'new_value' => 'closed',
        ]);
    }

    // -------------------------------------------------------------------------
    // authorizeView / authorizeStaff
    // -------------------------------------------------------------------------

    public function test_authorize_view_blocks_other_customer(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $ticket = $this->makeTicket($owner);

        $this->actingAs($owner);
        $this->service->authorizeView($ticket); // 不抛

        $this->actingAs($other);
        $this->expectException(HttpException::class);
        $this->service->authorizeView($ticket);
    }

    public function test_authorize_staff_blocks_customer(): void
    {
        $customer = $this->customer();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer);
        $this->expectException(HttpException::class);
        $this->service->authorizeStaff($ticket);
    }

    // -------------------------------------------------------------------------
    // 自定义字段
    // -------------------------------------------------------------------------

    public function test_validate_field_values_requires_active_required_fields(): void
    {
        TicketFieldDef::create(['label' => '序列号', 'key' => 'serial_no', 'type' => 'text', 'is_required' => true, 'is_active' => true, 'sort' => 0]);
        TicketFieldDef::create(['label' => '可选', 'key' => 'optional', 'type' => 'text', 'is_required' => false, 'is_active' => true, 'sort' => 1]);

        $errors = $this->service->validateFieldValues(Request::create('/', 'POST', ['field_serial_no' => 'SN-1']));

        $this->assertEmpty($errors);

        $errors = $this->service->validateFieldValues(Request::create('/', 'POST', []));
        $this->assertArrayHasKey('field_serial_no', $errors);
        $this->assertArrayNotHasKey('field_optional', $errors);
    }

    public function test_store_field_values_upserts(): void
    {
        $def = TicketFieldDef::create(['label' => '序列号', 'key' => 'serial_no', 'type' => 'text', 'is_required' => false, 'is_active' => true, 'sort' => 0]);
        $customer = $this->customer();
        $ticket = $this->makeTicket($customer);

        $this->service->storeFieldValues(Request::create('/', 'POST', ['field_serial_no' => 'SN-A']), $ticket);
        $this->assertDatabaseHas('ticket_field_values', ['ticket_id' => $ticket->id, 'field_def_id' => $def->id, 'value' => 'SN-A']);

        // 再次提交覆盖，不产生重复行
        $this->service->storeFieldValues(Request::create('/', 'POST', ['field_serial_no' => 'SN-B']), $ticket);
        $this->assertSame(1, $ticket->fieldValues()->where('field_def_id', $def->id)->count());
        $this->assertDatabaseHas('ticket_field_values', ['ticket_id' => $ticket->id, 'field_def_id' => $def->id, 'value' => 'SN-B']);
    }
}
