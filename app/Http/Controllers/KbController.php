<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 知识库管理（客服组）
 *
 * - 分类：列表/新增/编辑/删除（有文章时禁止删除）
 * - 文章：列表/新增/编辑/删除/发布切换；草稿仅作者/管理员可见
 * - 阅读页 /kb/{article} 统计浏览数
 */
class KbController extends Controller
{
    public function index(Request $request): View
    {
        $categories = KbCategory::orderBy('sort')->orderBy('id')->get();

        $query = KbArticle::with(['category', 'author:id,name'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('kb_category_id', (int) $request->input('category')))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->input('status') === 'published') {
                    $q->where('is_published', true);
                } elseif ($request->input('status') === 'draft') {
                    $q->where('is_published', false);
                }
            });

        // 草稿仅作者/管理员可见（读者视角不出现）
        $user = auth()->user();
        if (! $user->isAdmin()) {
            $query->where(fn ($q) => $q->where('is_published', true)->orWhere('created_by', $user->id));
        }

        $articles = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return view('kb.index', compact('categories', 'articles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticle($request);

        KbArticle::create($data + [
            'is_published' => $request->boolean('is_published', true),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.kb.index')->with('success', '文章已创建');
    }

    public function update(Request $request, KbArticle $article): RedirectResponse
    {
        abort_unless($this->canEdit($article), 403, '无权编辑该文章');

        $article->update($this->validateArticle($request) + [
            'is_published' => $request->boolean('is_published', $article->is_published),
        ]);

        return redirect()->route('admin.kb.index')->with('success', '文章已更新');
    }

    public function destroy(KbArticle $article): RedirectResponse
    {
        abort_unless($this->canEdit($article), 403, '无权删除该文章');

        $article->delete();

        return redirect()->route('admin.kb.index')->with('success', '文章已删除');
    }

    /**
     * 分类 CRUD（返回 JSON 供弹窗刷新）
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $category = KbCategory::create($data + ['sort' => $request->integer('sort', 0)]);

        return response()->json(['message' => '分类已创建', 'category' => $category]);
    }

    public function updateCategory(Request $request, KbCategory $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $category->update($data + ['sort' => $request->integer('sort', $category->sort)]);

        return response()->json(['message' => '分类已更新', 'category' => $category]);
    }

    public function destroyCategory(KbCategory $category): JsonResponse
    {
        if ($category->articles()->exists()) {
            return response()->json(['message' => '该分类下还有文章，不能删除'], 422);
        }

        $category->delete();

        return response()->json(['message' => '分类已删除']);
    }

    /**
     * 阅读页（读者视角，仅已发布；浏览数 +1）
     */
    public function show(KbArticle $article): View
    {
        abort_unless($article->is_published, 404);

        $article->increment('views');

        return view('kb.show', compact('article'));
    }

    /**
     * 编辑权限：作者本人 或 管理员
     */
    protected function canEdit(KbArticle $article): bool
    {
        $user = auth()->user();

        return $user->isAdmin() || $article->created_by === $user->id;
    }

    protected function validateArticle(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:50000'],
            'kb_category_id' => ['nullable', 'exists:kb_categories,id'],
        ]);
    }
}
