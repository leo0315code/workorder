<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ReportServiceTest：报表统计口径（页面与 CSV 导出共用同一套逻辑）
 * - summary / byStatus / byPriority / byCategory：基础计数与分组
 * - dailySeries / ratingDailySeries：趋势序列（无数据当天补 0 / null）
 * - agents：客服排行（handled/replies/avg_first_response_hours/avg_resolve_hours/overdue，SQLite julianday 分支）
 * - ratingStats：满意度（count/avg/positive/solved 及比率）
 * - normalizeDays / startOf：参数归一化
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ReportService;
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    /** 建一个 created_at 在指定时刻的工单（工厂不支持时间，forceFill 绕过 fillable） */
    private function ticketAt(User $customer, Carbon $at, array $attrs = []): Ticket
    {
        $ticket = Ticket::factory()->create(array_merge([
            'user_id' => $customer->id,
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
        ], $attrs));
        $ticket->forceFill(['created_at' => $at])->save();

        return $ticket;
    }

    private function replyAt(Ticket $ticket, User $user, Carbon $at): TicketReply
    {
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => 'reply-'.$at->format('His'),
            'type' => TicketReply::TYPE_REPLY,
        ]);
        $reply->forceFill(['created_at' => $at])->save();

        return $reply;
    }

    private function ratingAt(Ticket $ticket, User $user, int $rating, Carbon $at, bool $solved = true): void
    {
        // TicketRating::create 不支持 created_at（不在 fillable），forceFill 后保存
        $r = TicketRating::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'rating' => $rating,
            'is_solved' => $solved,
        ]);
        $r->forceFill(['created_at' => $at])->save();
    }

    // -------------------------------------------------------------------------
    // summary / byStatus / byPriority / byCategory
    // -------------------------------------------------------------------------

    public function test_summary_counts_within_window(): void
    {
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $this->ticketAt($customer, $start, ['status' => Ticket::STATUS_RESOLVED]);
        $this->ticketAt($customer, $start, ['status' => Ticket::STATUS_OPEN]);
        $this->ticketAt($customer, $start, ['status' => Ticket::STATUS_CLOSED]);
        // 窗口外的工单不计入
        $this->ticketAt($customer, $start->copy()->subDay(), ['status' => Ticket::STATUS_OPEN]);

        $s = $this->report->summary($start);

        $this->assertSame(3, $s['total']);
        $this->assertSame(2, $s['resolved']);
        $this->assertSame(1, $s['open']);
        $this->assertSame(0, $s['replies']);
    }

    public function test_by_status_and_priority_group(): void
    {
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $this->ticketAt($customer, $start, ['status' => Ticket::STATUS_OPEN, 'priority' => Ticket::PRIORITY_HIGH]);
        $this->ticketAt($customer, $start, ['status' => Ticket::STATUS_OPEN, 'priority' => Ticket::PRIORITY_LOW]);
        $this->ticketAt($customer, $start, ['status' => Ticket::STATUS_CLOSED, 'priority' => Ticket::PRIORITY_LOW]);

        $this->assertSame(2, $this->report->byStatus($start)['open']);
        $this->assertSame(1, $this->report->byStatus($start)['closed']);
        $this->assertSame(1, $this->report->byPriority($start)['high']);
        $this->assertSame(2, $this->report->byPriority($start)['low']);
    }

    public function test_by_category_groups_with_uncategorized(): void
    {
        $customer = $this->customer();
        $cat = Category::create(['name' => '售后', 'slug' => 'shouhou-test']);
        $start = Carbon::now()->subDays(6)->startOfDay();

        $this->ticketAt($customer, $start, ['category_id' => $cat->id]);
        $this->ticketAt($customer, $start, ['category_id' => $cat->id]);
        $this->ticketAt($customer, $start, []); // 未分类

        $by = $this->report->byCategory($start);

        $this->assertSame(2, $by['售后']);
        $this->assertSame(1, $by['未分类']);
    }

    // -------------------------------------------------------------------------
    // 趋势序列
    // -------------------------------------------------------------------------

    public function test_daily_series_fills_zero_for_empty_days(): void
    {
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $this->ticketAt($customer, $start->copy()->addDay());

        [$dates, $series] = $this->report->dailySeries($start);

        $this->assertCount(7, $dates);
        $this->assertCount(7, $series);
        $this->assertSame($start->copy()->addDay()->format('m-d'), $dates[1]);
        $this->assertSame(1, $series[1]);
        $this->assertSame(0, $series[0]);
    }

    public function test_rating_daily_series_null_avg_when_no_rating(): void
    {
        $customer = $this->customer();
        $ticket = $this->ticketAt($customer, Carbon::now()->subDays(6)->startOfDay());
        $start = Carbon::now()->subDays(6)->startOfDay();

        $this->ratingAt($ticket, $customer, 4, $start->copy()->addDay());

        [$dates, $avg, $count] = $this->report->ratingDailySeries($start);

        $this->assertCount(7, $dates);
        $this->assertSame(4.0, $avg[1]);
        $this->assertSame(1, $count[1]);
        $this->assertNull($avg[0]);
        $this->assertSame(0, $count[0]);
    }

    // -------------------------------------------------------------------------
    // agents 排行
    // -------------------------------------------------------------------------

    public function test_agents_ranks_handled_replies_and_first_response(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $ticket = $this->ticketAt($customer, $start, ['assignee_id' => $agent->id]);
        $this->replyAt($ticket, $agent, $start->copy()->addHours(2));

        $agents = $this->report->agents($start);
        $row = $agents->firstWhere('id', $agent->id);

        $this->assertSame(1, $row['handled']);
        $this->assertSame(1, $row['replies']);
        $this->assertSame(2.0, $row['avg_first_response_hours']);
        $this->assertNull($row['avg_resolve_hours']);
        $this->assertSame(0, $row['overdue']);
    }

    public function test_agents_computes_resolve_hours_for_closed_tickets(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $ticket = $this->ticketAt($customer, $start, [
            'assignee_id' => $agent->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);
        $ticket->update(['closed_at' => $start->copy()->addHours(8)]);

        $row = $this->report->agents($start)->firstWhere('id', $agent->id);

        $this->assertSame(8.0, $row['avg_resolve_hours']);
    }

    public function test_agents_counts_overdue_tickets(): void
    {
        $agent = $this->agent();
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $this->ticketAt($customer, $start, [
            'assignee_id' => $agent->id,
            'status' => Ticket::STATUS_OPEN,
            'sla_due_at' => Carbon::now()->subHour(),
        ]);

        $row = $this->report->agents($start)->firstWhere('id', $agent->id);

        $this->assertSame(1, $row['overdue']);
    }

    // -------------------------------------------------------------------------
    // ratingStats / 参数归一化
    // -------------------------------------------------------------------------

    public function test_rating_stats_computes_rates(): void
    {
        $customer = $this->customer();
        $start = Carbon::now()->subDays(6)->startOfDay();

        $t1 = $this->ticketAt($customer, $start);
        $t2 = $this->ticketAt($customer, $start);
        $t3 = $this->ticketAt($customer, $start);

        $this->ratingAt($t1, $customer, 5, $start->copy()->addHour(), true);
        $this->ratingAt($t2, $customer, 4, $start->copy()->addHour(), true);
        $this->ratingAt($t3, $customer, 2, $start->copy()->addHour(), false);

        $s = $this->report->ratingStats($start);

        $this->assertSame(3, $s['count']);
        $this->assertSame(3.67, $s['avg']);
        $this->assertSame(2, $s['positive']);
        $this->assertSame(2, $s['solved']);
        $this->assertSame(66.7, $s['positive_rate']);
        $this->assertSame(66.7, $s['solved_rate']);
    }

    public function test_rating_stats_zero_when_no_ratings(): void
    {
        $start = Carbon::now()->subDays(6)->startOfDay();

        $s = $this->report->ratingStats($start);

        $this->assertSame(0, $s['count']);
        $this->assertNull($s['avg']);
        $this->assertSame(0, $s['positive']);
        $this->assertSame(0, $s['positive_rate']);
    }

    public function test_normalize_days_accepts_7_30_90_and_defaults_to_30(): void
    {
        $this->assertSame(7, $this->report->normalizeDays(7));
        $this->assertSame(30, $this->report->normalizeDays(30));
        $this->assertSame(90, $this->report->normalizeDays(90));
        $this->assertSame(30, $this->report->normalizeDays(15));
        $this->assertSame(30, $this->report->normalizeDays('abc'));
        $this->assertSame(30, $this->report->normalizeDays(null));
    }

    public function test_start_of_returns_start_of_day(): void
    {
        $start = $this->report->startOf(30);
        $this->assertSame('00:00:00', $start->format('H:i:s'));
        $this->assertTrue($start->diffInDays(now()->endOfDay()) <= 30);
    }
}
