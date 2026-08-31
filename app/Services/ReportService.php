<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\TicketController;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\TicketReply;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 报表统计服务：页面与 CSV 导出共用同一套口径，避免两套逻辑漂移。
 */
class ReportService
{
    public function summary(Carbon $start): array
    {
        $tickets = Ticket::where('created_at', '>=', $start);

        return [
            'total' => (clone $tickets)->count(),
            'resolved' => (clone $tickets)->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])->count(),
            'open' => (clone $tickets)->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS])->count(),
            'replies' => TicketReply::where('created_at', '>=', $start)->where('type', TicketReply::TYPE_REPLY)->count(),
        ];
    }

    public function byStatus(Carbon $start): Collection
    {
        return Ticket::where('created_at', '>=', $start)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
    }

    public function byPriority(Carbon $start): Collection
    {
        return Ticket::where('created_at', '>=', $start)
            ->selectRaw('priority, COUNT(*) as c')->groupBy('priority')->pluck('c', 'priority');
    }

    public function byCategory(Carbon $start): Collection
    {
        return Ticket::where('created_at', '>=', $start)
            ->with('category')->get()->groupBy(fn ($t) => $t->category?->name ?? '未分类')
            ->map(fn ($g) => $g->count());
    }

    /**
     * 每日新增工单趋势：返回 [dates => ['m-d', ...], series => [int, ...]]
     */
    public function dailySeries(Carbon $start): array
    {
        $daily = Ticket::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $dates = [];
        $series = [];
        foreach (CarbonPeriod::create($start, now()->endOfDay())->toArray() as $date) {
            $dates[] = $date->format('m-d');
            $series[] = (int) ($daily[$date->format('Y-m-d')] ?? 0);
        }

        return [$dates, $series];
    }

    /**
     * 客服处理排行（页面与 CSV 共用）：
     * handled / replies / avg_first_response_hours / avg_resolve_hours / overdue
     */
    public function agents(Carbon $start): Collection
    {
        return User::whereIn('role', ['agent', 'admin'])
            ->withCount(['assignedTickets' => fn ($q) => $q->where('created_at', '>=', $start)])
            ->get()
            ->map(function ($agent) use ($start) {
                $replies = TicketReply::where('user_id', $agent->id)
                    ->where('created_at', '>=', $start)
                    ->count();

                // 平均首次响应时长：该客服在工单上的首条回复时间 - 工单创建时间
                // TIMESTAMPDIFF 是 MySQL 专用函数，SQLite 测试环境用 julianday 换算分钟数
                $driver = DB::connection()->getDriverName();
                $diffExpr = $driver === 'sqlite'
                    ? '(julianday(r.created_at) - julianday(t.created_at)) * 1440'
                    : 'TIMESTAMPDIFF(MINUTE, t.created_at, r.created_at)';
                $avgFirstResponse = DB::table('ticket_replies as r')
                    ->join('tickets as t', 't.id', '=', 'r.ticket_id')
                    ->where('r.user_id', $agent->id)
                    ->where('r.type', TicketReply::TYPE_REPLY)
                    ->where('r.created_at', '>=', $start)
                    ->whereRaw('r.created_at > t.created_at')
                    ->selectRaw("AVG({$diffExpr}) as avg")
                    ->value('avg');

                // 平均解决时长：该客服指派的已关闭工单（关闭时间 - 创建时间）
                $resolveExpr = $driver === 'sqlite'
                    ? '(julianday(t.closed_at) - julianday(t.created_at)) * 1440'
                    : 'TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)';
                $avgResolve = DB::table('tickets as t')
                    ->where('t.assignee_id', $agent->id)
                    ->where('t.status', Ticket::STATUS_CLOSED)
                    ->whereNotNull('t.closed_at')
                    ->where('t.created_at', '>=', $start)
                    ->selectRaw("AVG({$resolveExpr}) as avg")
                    ->value('avg');

                // SLA 超时数：该客服指派、未解决且已过 SLA 时限
                $overdueCount = Ticket::where('assignee_id', $agent->id)
                    ->where('created_at', '>=', $start)
                    ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                    ->where('sla_due_at', '<', now())
                    ->count();

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'handled' => $agent->assigned_tickets_count,
                    'replies' => $replies,
                    'avg_first_response_hours' => $avgFirstResponse === null
                        ? null
                        : round((float) $avgFirstResponse / 60, 1),
                    'avg_resolve_hours' => $avgResolve === null
                        ? null
                        : round((float) $avgResolve / 60, 1),
                    'overdue' => $overdueCount,
                ];
            })
            ->sortByDesc('replies')
            ->values();
    }

    /**
     * 满意度统计：count / avg / positive / solved / positive_rate / solved_rate
     */
    public function ratingStats(Carbon $start): array
    {
        $query = TicketRating::where('created_at', '>=', $start);

        $stats = [
            'count' => (clone $query)->count(),
            'avg' => (clone $query)->avg('rating'),
            'positive' => (clone $query)->where('rating', '>=', 4)->count(),
            'solved' => (clone $query)->where('is_solved', 1)->count(),
        ];

        $stats['avg'] = $stats['avg'] === null ? null : round((float) $stats['avg'], 2);
        $stats['positive_rate'] = $stats['count'] > 0
            ? round($stats['positive'] / $stats['count'] * 100, 1)
            : 0;
        $stats['solved_rate'] = $stats['count'] > 0
            ? round($stats['solved'] / $stats['count'] * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * 校验并归一化 days 参数（7/30/90，默认 30）
     */
    public function normalizeDays(mixed $days): int
    {
        $days = (int) $days;

        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    public function startOf(int $days): Carbon
    {
        return now()->subDays($days - 1)->startOfDay();
    }
}
