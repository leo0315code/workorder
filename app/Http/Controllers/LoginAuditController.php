<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LoginAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 登录审计（仅管理员）：查看所有登录/尝试登录记录
 */
class LoginAuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = LoginAudit::query()->with('user');

        // 筛选
        if ($request->filled('account')) {
            $query->where('email', 'like', '%'.trim($request->input('account')).'%');
        }
        if ($request->filled('channel') && in_array($request->input('channel'), ['web', 'admin', 'phone', 'wechat', 'api'], true)) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('result') && in_array($request->input('result'), ['success', 'failed'], true)) {
            $query->where('success', $request->input('result') === 'success');
        }

        $audits = $query->orderByDesc('login_at')->paginate(20)->withQueryString();

        $todayCount = LoginAudit::whereDate('login_at', today())->count();
        $failedToday = LoginAudit::whereDate('login_at', today())->where('success', false)->count();

        return view('login-audits.index', compact('audits', 'todayCount', 'failedToday'));
    }
}
