<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 工单分类管理（模块权限：categories）
 *
 * 注意：本控制器所有重定向必须使用带 admin 前缀的路由名（admin.categories.*），
 * 路由注册在 Route::prefix(config('app.admin_url'))->name('admin.') 分组内，
 * 若写成 route('categories.index') 会抛 RouteNotFoundException 导致 500。
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('tickets')->orderByDesc('updated_at')->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        // slug 自动生成：拼音化名称 + 随机后缀，避免重名冲突；仅内部使用，无需用户维护
        Category::create($data + ['slug' => Str::slug($data['name']).'-'.Str::random(4)]);

        return redirect()->route('admin.categories.index')->with('success', '分类已创建');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // is_active 未勾选时保持原值（checkbox 缺省即 false，直接覆盖会把分类误停用）
        $category->update($data + ['is_active' => $request->boolean('is_active', $category->is_active)]);

        return redirect()->route('admin.categories.index')->with('success', '分类已更新');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', '分类已删除');
    }
}
