<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 工单操作审计（仅管理员）：跨工单检索全部操作日志
 * 数据源 TicketLog（工单详情内的时间线同表），此处提供全局查询视图。
 */
class TicketLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = TicketLog::query()
            ->with(['ticket:id,no,subject', 'user:id,name,role']);

        // 操作人
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        // 动作类型（白名单，防任意注入）
        if ($request->filled('action') && in_array($request->input('action'), array_keys(TicketLog::DESCRIPTIONS), true)) {
            $query->where('action', $request->input('action'));
        }

        // 关键字：工单编号 / 主题 / 备注
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('ticket', function ($t) use ($q) {
                    $t->where('no', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%");
                })->orWhere('note', 'like', "%{$q}%");
            });
        }

        // 时间范围
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $logs = $query->orderByDesc('id')->paginate(30)->withQueryString();

        // 操作人下拉与今日汇总
        $users = User::whereHas('ticketLogs')->orderBy('name')->get(['id', 'name', 'role']);

        return view('ticket-logs.index', compact('logs', 'users'));
    }
}
