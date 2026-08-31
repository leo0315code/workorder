<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 侧边栏菜单管理（仅管理员）
 *
 * 菜单条目存 menus 表，路由/权限仍以代码为准：
 * - route_name 不存在时侧栏自动跳过（MenuService 防御），此处只做格式校验不校验存在性
 * - module 绑定 User::AGENT_MODULES 的模块键
 */
class MenuController extends Controller
{
    public function index(): View
    {
        $menus = Menu::orderBy('audience')->orderBy('sort')->orderBy('id')->get();
        $modules = User::AGENT_MODULES;

        return view('menus.index', compact('menus', 'modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Menu::create($data + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('admin.menus.index')->with('success', '菜单已创建');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $menu->update($this->validateData($request) + ['is_active' => $request->boolean('is_active', $menu->is_active)]);

        return redirect()->route('admin.menus.index')->with('success', '菜单已更新');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', '菜单已删除');
    }

    /**
     * 单一字段快速更新（内联下拉/开关提交）
     * 仅允许白名单字段，避免通过此接口改 label/route_name
     */
    public function updateField(Request $request, Menu $menu): RedirectResponse
    {
        $allowed = ['audience', 'admin_only', 'is_active'];

        $data = $request->validate([
            'field' => ['required', 'in:'.implode(',', $allowed)],
            'value' => ['required'],
        ]);

        $value = $data['value'];
        if (in_array($data['field'], ['admin_only', 'is_active'], true)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        $menu->update([$data['field'] => $value]);

        return back()->with('success', '已更新');
    }

    /**
     * 菜单字段校验（store / update 共用）
     */
    protected function validateData(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'route_name' => ['nullable', 'string', 'max:100'],
            'audience' => ['required', 'in:agent,customer'],
            'admin_only' => ['nullable', 'boolean'],
            'icon' => ['required', 'string', 'max:30'],
            'module' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:30'],
            'active_pattern' => ['nullable', 'string', 'max:100'],
            'except_pattern' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
