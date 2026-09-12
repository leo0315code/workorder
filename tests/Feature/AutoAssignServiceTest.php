<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AutoAssignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AutoAssignServiceTest：自动分配核心逻辑
 * - pickFromCandidates 纯函数：实时不可用回退全量 / 在线优先 / 无人在线返回 null
 * - pick() 集成：开关关闭、无候选、候选排序（agent 优先 admin、活跃工单数升序）、manual_offline 排除
 * - 在线集合通过 AutoAssignService::$onlineUidsProvider 注入（避免依赖真实 WS 服务）
 */
class AutoAssignServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AutoAssignService::$onlineUidsProvider = null;
    }

    protected function tearDown(): void
    {
        AutoAssignService::$onlineUidsProvider = null;
        parent::tearDown();
    }

    private function agent(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'agent', 'manual_offline' => false], $attrs));
    }

    private function admin(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin', 'manual_offline' => false], $attrs));
    }

    private function enableAutoAssign(): void
    {
        Setting::updateOrCreate(['setting_key' => 'auto_assign'], ['value' => '1']);
    }

    private function disableAutoAssign(): void
    {
        Setting::updateOrCreate(['setting_key' => 'auto_assign'], ['value' => '0']);
    }

    private function activeTicketFor(User $agent): Ticket
    {
        return Ticket::factory()->create(['assignee_id' => $agent->id, 'status' => Ticket::STATUS_OPEN]);
    }

    private function resolvedTicketFor(User $agent): Ticket
    {
        return Ticket::factory()->create(['assignee_id' => $agent->id, 'status' => Ticket::STATUS_RESOLVED]);
    }

    private function withOnline(array $ids): void
    {
        AutoAssignService::$onlineUidsProvider = fn () => array_fill_keys($ids, true);
    }

    private function withGatewayDown(): void
    {
        // 实时服务不可用：onlineUids() 返回 null（语义与真实异常路径一致）
        AutoAssignService::$onlineUidsProvider = fn () => null;
    }

    // -------------------------------------------------------------------------
    // pickFromCandidates 纯函数
    // -------------------------------------------------------------------------

    public function test_pick_from_candidates_returns_first_when_online_unavailable(): void
    {
        $a = $this->agent();
        $b = $this->agent();

        $this->assertSame($a->id, AutoAssignService::pickFromCandidates(collect([$a, $b]), null));
    }

    public function test_pick_from_candidates_prefers_first_online(): void
    {
        $a = $this->agent();
        $b = $this->agent();

        // 候选顺序 a,b；只有 b 在线 → 选 b
        $this->assertSame($b->id, AutoAssignService::pickFromCandidates(
            collect([$a, $b]),
            [$b->id => true]
        ));
    }

    public function test_pick_from_candidates_returns_null_when_nobody_online(): void
    {
        $a = $this->agent();
        $b = $this->agent();

        $this->assertNull(AutoAssignService::pickFromCandidates(collect([$a, $b]), []));
    }

    public function test_pick_from_candidates_returns_null_for_empty_candidates(): void
    {
        $this->assertNull(AutoAssignService::pickFromCandidates(collect(), null));
        $this->assertNull(AutoAssignService::pickFromCandidates(collect(), []));
    }

    // -------------------------------------------------------------------------
    // pick() 集成：开关与候选筛选
    // -------------------------------------------------------------------------

    public function test_pick_returns_null_when_disabled(): void
    {
        $this->disableAutoAssign();
        $this->agent();

        // 开关关闭：pick() 直接返回，不触碰在线查询
        $this->assertNull(AutoAssignService::pick());
    }

    public function test_pick_returns_null_with_no_agent_or_admin(): void
    {
        $this->enableAutoAssign();
        User::factory()->create(['role' => 'customer']);

        $this->assertNull(AutoAssignService::pick());
    }

    public function test_pick_ignores_manually_offline_agents(): void
    {
        $this->enableAutoAssign();
        $online = $this->agent();
        $offline = $this->agent(['manual_offline' => true]);

        $this->withOnline([$online->id, $offline->id]);

        $this->assertSame($online->id, AutoAssignService::pick());
    }

    public function test_pick_orders_agent_before_admin_at_same_load(): void
    {
        $this->enableAutoAssign();
        $agent = $this->agent();
        $admin = $this->admin();

        $this->withOnline([$agent->id, $admin->id]);

        $this->assertSame($agent->id, AutoAssignService::pick());
    }

    public function test_pick_favors_lowest_active_load(): void
    {
        $this->enableAutoAssign();
        $busy = $this->agent();
        $idle = $this->agent();
        $this->activeTicketFor($busy);
        $this->activeTicketFor($busy);
        $this->activeTicketFor($idle);

        $this->withOnline([$busy->id, $idle->id]);

        // 两者都在线，负载少的 idle 优先
        $this->assertSame($idle->id, AutoAssignService::pick());
    }

    public function test_pick_does_not_count_resolved_tickets_as_load(): void
    {
        $this->enableAutoAssign();
        $withResolvedOnly = $this->agent();
        $this->resolvedTicketFor($withResolvedOnly);
        $this->resolvedTicketFor($withResolvedOnly);

        $this->withOnline([$withResolvedOnly->id]);

        // 已解决/关闭不计入负载 → 候选负载 0，应被选中
        $this->assertSame($withResolvedOnly->id, AutoAssignService::pick());
    }

    public function test_pick_returns_null_when_nobody_online(): void
    {
        $this->enableAutoAssign();
        $this->agent();
        $this->admin();

        // 无人在线
        $this->withOnline([]);

        $this->assertNull(AutoAssignService::pick());
    }

    public function test_pick_falls_back_to_first_candidate_when_gateway_unavailable(): void
    {
        $this->enableAutoAssign();
        $a = $this->agent();
        $b = $this->agent();

        // 实时服务不可用（异常 → onlineUids 返回 null）→ 退回全量候选第一个
        $this->withGatewayDown();

        $this->assertSame($a->id, AutoAssignService::pick());
    }
}
