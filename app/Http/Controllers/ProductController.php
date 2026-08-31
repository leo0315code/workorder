<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 产品管理（模块权限：products）
 *
 * 注意：本控制器所有重定向必须使用带 admin 前缀的路由名（admin.products.*），
 * 路由注册在 name('admin.') 分组内，写成 route('products.index') 会抛
 * RouteNotFoundException 导致 500（历史上因此修过一次）。
 */
class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::withCount('tickets')->orderByDesc('updated_at')->paginate(15);

        return view('products.index', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // SKU 留空时自动生成大写随机串，保证唯一可追溯
        // 注意：必须先赋值再 create——若用 $data + ['sku' => ...] 数组 union，
        // 已存在的空 '' key 会覆盖右侧默认值，导致自动生成失效
        $data['sku'] = $data['sku'] ?: strtoupper(Str::random(8));

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', '产品已创建');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));

        return redirect()->route('admin.products.index')->with('success', '产品已更新');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', '产品已删除');
    }

    /**
     * 产品字段校验；is_active 缺省视为启用（新建设备默认上架）
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'warranty_days' => ['nullable', 'integer', 'min:0', 'max:36500'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
