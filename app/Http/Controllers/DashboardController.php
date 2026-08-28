<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        if ($user->isAgent()) {
            return $this->staffDashboard();
        }

        return $this->customerDashboard();
    }

    /**
     * 「最近工单」区块的局部刷新接口（AJAX，不整页刷新）
     */
    public function recentFragment(Request $request): \Illuminate\Http\Response
    {
        $user = Auth::user();
        $data = $this->recentData($request->input('scope', 'all'));

        // 片段接口禁缓存（避免浏览器启发式缓存导致 tab 高亮/内容陈旧）
        return response()->view('dashboard.partials.recent', $data)
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * 计算「最近工单」查询与统计数据
     */
    protected function recentData(string $scope): array
    {
        $scope = in_array($scope, ['all', 'open', 'resolved'], true) ? $scope : 'all';

        $byStatus = Ticket::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $total = Ticket::count();

        $recentQuery = Ticket::with(['user', 'category', 'assignee'])->orderByDesc('updated_at');
        if ($scope === 'open') {
            $recentQuery->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS]);
        } elseif ($scope === 'resolved') {
            $recentQuery->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }
        $recent = $recentQuery->limit(10)->get();

        $openCount = ($byStatus['open'] ?? 0) + ($byStatus['pending'] ?? 0) + ($byStatus['in_progress'] ?? 0);
        $resolvedCount = ($byStatus['resolved'] ?? 0) + ($byStatus['closed'] ?? 0);

        return compact('scope', 'recent', 'total', 'openCount', 'resolvedCount', 'byStatus');
    }

    protected function staffDashboard()
    {
        $total = Ticket::count();
        $open = Ticket::whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS])->count();
        $resolvedToday = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('updated_at', today())
            ->count();
        $overdue = Ticket::whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->where('sla_due_at', '<', now())
            ->count();

        $byStatus = Ticket::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $byPriority = Ticket::selectRaw('priority, count(*) as c')->groupBy('priority')->pluck('c', 'priority');
        $byCategory = Ticket::with('category')->get()->groupBy(fn ($t) => $t->category?->name ?? '未分类')
            ->map(fn ($g) => $g->count());

        // 「最近工单」按 scope 过滤：all/open/resolved
        $scope = request('scope', 'all');
        $recentData = $this->recentData($scope);
        $recent = $recentData['recent'];

        $myOpen = Ticket::where('assignee_id', Auth::id())
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->count();
        $unassigned = Ticket::whereNull('assignee_id')
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->count();

        return view('dashboard', array_merge(
            compact('total', 'open', 'resolvedToday', 'overdue', 'byStatus', 'byPriority', 'byCategory', 'recent', 'myOpen', 'unassigned', 'scope'),
            $recentData
        ));
    }

    protected function customerDashboard()
    {
        $my = Ticket::where('user_id', Auth::id());

        $total = (clone $my)->count();
        $open = (clone $my)->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS])->count();
        $resolved = (clone $my)->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])->count();

        $byStatus = (clone $my)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $byPriority = (clone $my)->selectRaw('priority, count(*) as c')->groupBy('priority')->pluck('c', 'priority');
        $recent = (clone $my)->with(['category', 'assignee'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        return view('dashboard', compact('total', 'open', 'resolved', 'byStatus', 'byPriority', 'recent'));
    }
}
