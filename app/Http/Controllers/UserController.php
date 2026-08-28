<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AutoAssignService;
use App\Services\WebSocketService;
use GatewayClient\Gateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount(['tickets', 'assignedTickets']);

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        // 在线 uid 集合（用于展示实时状态）
        $onlineUids = AutoAssignService::onlineUids() ?: [];
        $agentRoles = \App\Models\AgentRole::where('is_active', true)->orderBy('sort')->orderBy('id')->get();

        return view('users.index', compact('users', 'onlineUids', 'agentRoles'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate(['role' => ['required', 'in:customer,agent,admin']]);

        // 不允许降级自己（避免把自己踢出管理）
        if ($user->id === $request->user()->id && $request->input('role') !== 'admin') {
            return back()->with('error', '不能修改自己的角色');
        }

        $user->update(['role' => $request->input('role')]);

        return back()->with('success', '用户 '.$user->name.' 角色已更新为 '.$request->input('role'));
    }

    /**
     * 设置客服模块权限（菜单显示 + 后端守卫）
     */
    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        // 不能修改自己的权限
        if ($user->id === $request->user()->id) {
            return back()->with('error', '不能修改自己的权限');
        }

        if ($user->role === 'customer') {
            return back()->with('error', '客户账号无后台模块权限');
        }

        $valid = implode(',', array_keys(User::AGENT_MODULES));
        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['in:'.$valid],
        ]);

        $user->update(['permissions' => $request->input('modules', [])]);

        return back()->with('success', '用户 '.$user->name.' 的模块权限已更新');
    }

    /**
     * 给客服分配角色（细粒度模块授权）
     */
    public function updateAgentRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', '不能修改自己的角色');
        }
        if ($user->role === 'customer') {
            return back()->with('error', '客户账号无客服角色');
        }
        $data = $request->validate([
            'agent_role_id' => ['nullable', 'exists:agent_roles,id'],
        ]);
        $user->update(['agent_role_id' => $data['agent_role_id'] ?: null]);
        return back()->with('success', '用户 '.$user->name.' 的客服角色已更新');
    }

    /**
     * 管理员手动置为离线/恢复在线（离线客服不参与自动分配；可选断开其连接）
     */
    public function toggleOffline(Request $request, User $user): RedirectResponse
    {
        // 不能把自己置为离线
        if ($user->id === $request->user()->id) {
            return back()->with('error', '不能设置自己的在线状态');
        }

        $offline = $user->isManuallyOffline();
        $user->update(['manual_offline' => ! $offline]);

        // 置为离线时，若其 WS 在线则强制断开（踢下线）
        if (! $offline) {
            try {
                WebSocketService::boot();
                foreach (Gateway::getClientIdByUid($user->id) as $clientId) {
                    Gateway::closeClient($clientId, '管理员已将你设为离线');
                }
            } catch (\Throwable $e) {
                // 实时服务不可用时忽略（仅标记离线，仍不参与分配）
            }
        }

        return back()->with('success', $user->name.' 已'.($offline ? '恢复在线' : '设为离线'));
    }
}
