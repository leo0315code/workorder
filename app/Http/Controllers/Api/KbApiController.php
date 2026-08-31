<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 知识库 API（面向 App/小程序）
 *
 * 只暴露已发布文章；分类 + 文章列表（可按分类/关键词筛选）+ 详情（浏览数 +1）
 */
class KbApiController extends Controller
{
    /**
     * 分类列表（含各分类已发布文章数）
     */
    public function categories(): JsonResponse
    {
        $items = KbCategory::orderBy('sort')->orderBy('id')
            ->get()
            ->map(fn (KbCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'article_count' => $c->articles()->where('is_published', true)->count(),
            ]);

        return response()->json(['items' => $items]);
    }

    /**
     * 文章列表（仅已发布；按分类/关键词筛选）
     */
    public function index(Request $request): JsonResponse
    {
        $query = KbArticle::with('category:id,name')
            ->where('is_published', true);

        if ($request->filled('category_id')) {
            $query->where('kb_category_id', $request->integer('category_id'));
        }
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('content', 'like', "%{$q}%"));
        }

        $articles = $query->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return response()->json([
            'items' => $articles->getCollection()->map(fn (KbArticle $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'category' => $a->category?->name,
                'views' => $a->views,
                'updated_at' => $a->updated_at?->toDateString(),
            ]),
            'pagination' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * 文章详情（仅已发布；浏览数 +1）
     */
    public function show(KbArticle $article): JsonResponse
    {
        abort_unless($article->is_published, 404, '文章不存在');

        $article->increment('views');

        return response()->json([
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content, // Markdown 原文，前端自行渲染
                'category' => $article->category?->name,
                'views' => $article->views,
                'updated_at' => $article->updated_at?->toDateTimeString(),
            ],
        ]);
    }
}
