<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $days = (int) $request->input('days', 30);
        $days = in_array($days, [7, 30, 90]) ? $days : 30;
        $start = now()->subDays($days - 1)->startOfDay();

        // 每日新增工单趋势
        $daily = Ticket::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $dates = [];
        $dailySeries = [];
        foreach (CarbonPeriod::create($start, now()->endOfDay())->toArray() as $date) {
            $key = $date->format('Y-m-d');
            $dates[] = $date->format('m-d');
            $dailySeries[] = (int) ($daily[$key] ?? 0);
        }

        // 客服处理排行
        $agents = User::whereIn('role', ['agent', 'admin'])
            ->withCount(['assignedTickets' => fn ($q) => $q->where('created_at', '>=', $start)])
            ->get()
            ->map(function ($agent) use ($start) {
                $replies = TicketReply::where('user_id', $agent->id)
                    ->where('created_at', '>=', $start)
                    ->count();
                // 平均首次响应时长：该客服在工单上的首条回复时间 - 工单创建时间
                $avgFirstResponse = DB::table('ticket_replies as r')
                    ->join('tickets as t', 't.id', '=', 'r.ticket_id')
                    ->where('r.user_id', $agent->id)
                    ->where('r.type', TicketReply::TYPE_REPLY)
                    ->where('r.created_at', '>=', $start)
                    ->whereRaw('r.created_at > t.created_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, t.created_at, r.created_at)) as avg')
                    ->value('avg');

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'handled' => $agent->assigned_tickets_count,
                    'replies' => $replies,
                    'avg_first_response_hours' => $avgFirstResponse === null
                        ? null
                        : round((float) $avgFirstResponse / 60, 1),
                ];
            })
            ->sortByDesc('replies')
            ->values();

        // 分布
        $byStatus = Ticket::where('created_at', '>=', $start)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $byPriority = Ticket::where('created_at', '>=', $start)
            ->selectRaw('priority, COUNT(*) as c')->groupBy('priority')->pluck('c', 'priority');
        $byCategory = Ticket::where('created_at', '>=', $start)
            ->with('category')->get()->groupBy(fn ($t) => $t->category?->name ?? '未分类')
            ->map(fn ($g) => $g->count());

        $summary = [
            'total' => Ticket::where('created_at', '>=', $start)->count(),
            'resolved' => Ticket::where('created_at', '>=', $start)->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])->count(),
            'open' => Ticket::where('created_at', '>=', $start)->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS])->count(),
            'replies' => TicketReply::where('created_at', '>=', $start)->where('type', TicketReply::TYPE_REPLY)->count(),
        ];

        // 满意度：平均分 + 满意率（4 星及以上）
        $ratingQuery = \App\Models\TicketRating::where('created_at', '>=', $start);
        $ratingStats = [
            'count' => (clone $ratingQuery)->count(),
            'avg' => (clone $ratingQuery)->avg('rating'),
            'positive' => (clone $ratingQuery)->where('rating', '>=', 4)->count(),
        ];
        $ratingStats['avg'] = $ratingStats['avg'] === null ? null : round((float) $ratingStats['avg'], 2);
        $ratingStats['positive_rate'] = $ratingStats['count'] > 0
            ? round($ratingStats['positive'] / $ratingStats['count'] * 100, 1)
            : 0;

        return view('reports.index', compact('days', 'dates', 'dailySeries', 'agents', 'byStatus', 'byPriority', 'byCategory', 'summary', 'ratingStats'));
    }
}
