<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 基础数据 API：产品 / 客户（客户仅客服可见）
 */
class ReferenceApiController extends Controller
{
    /**
     * 产品列表（客户创建工单时选产品用）
     */
    public function products(Request $request): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku]);

        return response()->json(['items' => $products]);
    }

    /**
     * 标签列表（登录即可；工单按标签筛选/展示用）
     */
    public function tags(): JsonResponse
    {
        $tags = \App\Models\Tag::orderBy('name')
            ->get(['id', 'name', 'color'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]);

        return response()->json(['items' => $tags]);
    }

    /**
     * 客户列表（仅客服/管理员，创建/指派工单时选客户）
     */
    public function customers(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAgent(), 403, '无权访问');

        $customers = \App\Models\Customer::with('product:id,name')
            ->orderBy('company')
            ->get(['id', 'company', 'contact_name', 'phone', 'product_id'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'company' => $c->company,
                'contact_name' => $c->contact_name,
                'phone' => $c->phone,
                'product' => $c->product?->name,
            ]);

        return response()->json(['items' => $customers]);
    }
}
