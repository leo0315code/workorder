<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

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
        $recentQuery = Ticket::with(['user', 'category', 'assignee'])->orderByDesc('updated_at');
        if ($scope === 'open') {
            $recentQuery->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_IN_PROGRESS]);
        } elseif ($scope === 'resolved') {
            $recentQuery->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }
        $recent = $recentQuery->limit(10)->get();

        $myOpen = Ticket::where('assignee_id', Auth::id())
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->count();
        $unassigned = Ticket::whereNull('assignee_id')
            ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->count();

        return view('dashboard', compact(
            'total', 'open', 'resolvedToday', 'overdue',
            'byStatus', 'byPriority', 'byCategory', 'recent', 'myOpen', 'unassigned', 'scope'
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
