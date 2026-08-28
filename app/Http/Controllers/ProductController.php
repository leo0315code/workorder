<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

        Product::create($data + ['sku' => $data['sku'] ?: strtoupper(Str::random(8))]);

        return redirect()->route('products.index')->with('success', '产品已创建');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));

        return redirect()->route('products.index')->with('success', '产品已更新');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', '产品已删除');
    }

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
