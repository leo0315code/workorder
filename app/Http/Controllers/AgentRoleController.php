<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AgentRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 客服角色模板管理（模块权限：agent-roles，仅管理员）
 *
 * 角色模板将一组模块权限打包（modules 数组），分配到具体客服后，
 * 由 User::canAccessModule() + module: 中间件共同决定其可见菜单与后端权限。
 */
class AgentRoleController extends Controller
{
    public function index(): View
    {
        $roles = AgentRole::withCount('users')->orderBy('sort')->orderBy('id')->paginate(15);

        return view('agent-roles.index', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        // checkbox 未勾选时 boolean() 返回 false，需在表单显式勾选「启用」
        AgentRole::create($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.agent-roles.index')->with('success', '角色已创建');
    }

    public function update(Request $request, AgentRole $role): RedirectResponse
    {
        $data = $this->validateData($request, $role);

        $role->update($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.agent-roles.index')->with('success', '角色已更新');
    }

    public function destroy(AgentRole $role): RedirectResponse
    {
        // 角色仍被用户引用时禁止删除，防止用户权限悬空
        if ($role->users()->exists()) {
            return back()->with('error', '该角色还有 '.($role->users_count).' 个用户正在使用，请先调整用户角色');
        }
        $role->delete();

        return redirect()->route('admin.agent-roles.index')->with('success', '角色已删除');
    }

    /**
     * 角色模板校验（store / update 共用）
     *
     * @param  AgentRole|null  $role  更新时传入当前角色，用于 name 唯一性排除自身
     */
    protected function validateData(Request $request, ?AgentRole $role = null): array
    {
        return $request->validate([
            // name 是内部标识（小写字母/数字/下划线），label 才是显示名
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('agent_roles', 'name')->ignore($role?->id)],
            'label' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['in:'.implode(',', array_keys(\App\Models\User::AGENT_MODULES))],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
