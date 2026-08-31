@extends('layouts.app')

@section('page_title', '知识库')

@section('content')
    <div x-data="kbManager()">
        {{-- 工具栏：分类管理 + 新建文章 --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                {{-- 分类快捷筛选 --}}
                <a href="{{ route('admin.kb.index') }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ ! request('category') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">全部</a>
                @foreach ($categories as $c)
                    <a href="{{ route('admin.kb.index', ['category' => $c->id] + request()->except('category')) }}"
                       class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ (string) request('category') === (string) $c->id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">{{ $c->name }}</a>
                @endforeach
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="openCategoryModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m-7 6h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" /></svg>
                    分类管理
                </button>
                <button type="button" @click="openCreate()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    新建文章
                </button>
            </div>
        </div>

        {{-- 搜索 + 状态筛选 --}}
        <form method="GET" action="{{ route('admin.kb.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="搜索文章标题…"
                   class="w-64 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">全部状态</option>
                <option value="published" @selected(request('status') === 'published')>已发布</option>
                <option value="draft" @selected(request('status') === 'draft')>草稿</option>
            </select>
            <button type="submit" class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-700 transition">搜索</button>
            @if (request()->filled('q') || request()->filled('status') || request()->filled('category'))
                <a href="{{ route('admin.kb.index') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">重置</a>
            @endif
        </form>

        {{-- 文章列表 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            @forelse ($articles as $a)
                <div class="flex items-center gap-4 border-b border-gray-100 dark:border-gray-800/60 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition last:border-0">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('kb.show', $a) }}" target="_blank"
                               class="font-medium text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400 truncate">{{ $a->title }}</a>
                            @if (! $a->is_published)
                                <span class="shrink-0 rounded-md bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">草稿</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-400 flex items-center gap-2">
                            <span>{{ $a->category?->name ?? '未分类' }}</span>
                            <span>·</span>
                            <span>{{ $a->author?->name ?? '—' }}</span>
                            <span>·</span>
                            <span>{{ $a->updated_at?->format('Y-m-d H:i') }}</span>
                            <span>·</span>
                            <span>{{ $a->views }} 次阅读</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <form method="POST" action="{{ route('admin.kb.update', $a) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="title" value="{{ $a->title }}">
                            <input type="hidden" name="content" value="{{ $a->content }}">
                            <input type="hidden" name="kb_category_id" value="{{ $a->kb_category_id }}">
                            <input type="hidden" name="is_published" value="{{ $a->is_published ? '0' : '1' }}">
                            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:underline">
                                {{ $a->is_published ? '转为草稿' : '发布' }}
                            </button>
                        </form>
                        <button type="button" @click="openEdit(@js($a->only(['id', 'title', 'content', 'kb_category_id', 'is_published'])))"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">编辑</button>
                        <form method="POST" action="{{ route('admin.kb.destroy', $a) }}" class="inline" onsubmit="return confirm('确定删除该文章？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">删除</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="py-14 text-center text-gray-400 text-sm">暂无文章，点击右上角「新建文章」开始沉淀知识</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $articles->links() }}</div>

        {{-- 新建/编辑文章弹窗 --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-12 bg-gray-900/50" @click.self="open = false">
            <div class="w-full max-w-2xl rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="editing ? '编辑文章' : '新建文章'"></h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>
                <form :action="editing ? '/console/kb/' + editing.id : '{{ route('admin.kb.store') }}'" method="POST" class="px-5 py-4 space-y-4">
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PATCH">
                    </template>
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">标题 <span class="text-red-500">*</span></label>
                            <input type="text" name="title" x-model="form.title" required maxlength="200"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">分类</label>
                            <select name="kb_category_id" x-model="form.kb_category_id"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">未分类</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">内容 <span class="text-red-500">*</span></label>
                        <textarea name="content" x-model="form.content" rows="12" required
                                  class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm font-mono leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="支持 Markdown 语法（标题 / 列表 / 代码块…）"></textarea>
                        <p class="mt-1 text-xs text-gray-400">支持 Markdown，保存后可点标题在新标签页预览渲染效果</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_published" value="1" x-model="form.is_published"
                                   class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">发布（不勾选=草稿）</span>
                        </label>
                        <div class="flex gap-3">
                            <button type="button" @click="open = false"
                                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">取消</button>
                            <button type="submit" class="rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 transition">保存</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- 分类管理弹窗 --}}
        <div x-show="catOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-16 bg-gray-900/50" @click.self="catOpen = false">
            <div class="w-full max-w-md rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">分类管理</h3>
                    <button type="button" @click="catOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>
                <div class="px-5 py-4 space-y-2 max-h-[60vh] overflow-y-auto">
                    <template x-for="c in categories" :key="c.id">
                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-800 px-3 py-2">
                            <input type="text" x-model="c.name" @keydown.enter="updateCategory(c)"
                                   class="flex-1 rounded-lg border border-transparent dark:bg-gray-800 px-2 py-1 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 bg-transparent">
                            <input type="number" x-model="c.sort" @change="updateCategory(c)" title="排序（升序）"
                                   class="w-16 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="deleteCategory(c)" class="text-xs text-red-500 hover:underline shrink-0">删除</button>
                        </div>
                    </template>
                    <template x-if="categories.length === 0">
                        <p class="py-6 text-center text-sm text-gray-400">暂无分类</p>
                    </template>
                    <div class="flex gap-2 pt-2">
                        <input type="text" x-model="newCategoryName" placeholder="新分类名称"
                               class="flex-1 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                        <button type="button" @click="addCategory()"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition">添加</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function kbManager() {
                return {
                    open: false,
                    editing: null,
                    form: { title: '', content: '', kb_category_id: '', is_published: true },
                    // 分类
                    catOpen: false,
                    categories: @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'sort' => $c->sort])),
                    newCategoryName: '',

                    openCreate() {
                        this.editing = null;
                        this.form = { title: '', content: '', kb_category_id: '', is_published: true };
                        this.open = true;
                    },
                    openEdit(a) {
                        this.editing = a;
                        this.form = { title: a.title, content: a.content, kb_category_id: a.kb_category_id || '', is_published: !!a.is_published };
                        this.open = true;
                    },
                    openCategoryModal() { this.catOpen = true; },

                    async addCategory() {
                        const name = this.newCategoryName.trim();
                        if (!name) return;
                        const r = await fetch('{{ route('admin.kb.categories.store') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ name }),
                        });
                        const d = await r.json();
                        if (r.ok) { this.categories.push(d.category); this.newCategoryName = ''; }
                    },
                    async updateCategory(c) {
                        await fetch('/console/kb/categories/' + c.id, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ name: c.name, sort: c.sort }),
                        });
                    },
                    async deleteCategory(c) {
                        if (!confirm('确定删除分类「' + c.name + '」？')) return;
                        const r = await fetch('/console/kb/categories/' + c.id, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        });
                        const d = await r.json();
                        if (r.ok) { this.categories = this.categories.filter(x => x.id !== c.id); }
                        else { alert(d.message || '删除失败'); }
                    },
                };
            }
        </script>
    @endpush
@endsection
