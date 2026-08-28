<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AgentRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
        if ($role->users()->exists()) {
            return back()->with('error', '该角色还有 '.($role->users_count).' 个用户正在使用，请先调整用户角色');
        }
        $role->delete();

        return redirect()->route('admin.agent-roles.index')->with('success', '角色已删除');
    }

    protected function validateData(Request $request, ?AgentRole $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('agent_roles', 'name')->ignore($role?->id)],
            'label' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['in:'.implode(',', array_keys(\App\Models\User::AGENT_MODULES))],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
