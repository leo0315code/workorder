@extends('layouts.app')

@section('page_title', '工单模板')

@section('content')
    @php $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->get(); $products = \App\Models\Product::where('is_active', true)->orderBy('name')->get(); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 表单 --}}
        <div x-data="templateForm()">
            <form method="POST" action="{{ route('admin.ticket-templates.store') }}" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="editing ? '编辑模板' : '新建模板'"></h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">模板名称 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="100" placeholder="如：网络故障报修" x-model="form.name"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">主题（自动填充）<span class="text-red-500">*</span></label>
                    <input type="text" name="subject" required maxlength="255" placeholder="工单标题" x-model="form.subject"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">描述（自动填充）<span class="text-red-500">*</span></label>
                    <textarea name="description" required rows="4" maxlength="10000" x-model="form.description"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">分类</label>
                        <select name="category_id" x-model="form.category_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            <option value="">不指定</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">产品</label>
                        <select name="product_id" x-model="form.product_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            <option value="">不指定</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">优先级</label>
                        <select name="priority" x-model="form.priority" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            @foreach (\App\Http\Controllers\TicketController::PRIORITY_NAMES as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600">
                        启用
                    </label>
                    <div class="flex gap-2">
                        <button type="button" x-show="editing" @click="reset()" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300">取消</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500" x-text="editing ? '保存修改' : '创建模板'"></button>
                    </div>
                </div>
            </form>
        </div>

        {{-- 列表 --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                            <th class="py-3 px-4">名称</th>
                            <th class="py-3 px-4">主题</th>
                            <th class="py-3 px-4">分类/产品</th>
                            <th class="py-3 px-4">优先级</th>
                            <th class="py-3 px-4">状态</th>
                            <th class="py-3 px-4 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $t)
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">{{ $t->name }}</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400 max-w-[200px] truncate">{{ $t->subject }}</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $t->category?->name ?? '—' }} / {{ $t->product?->name ?? '—' }}</td>
                                <td class="py-3 px-4"><x-ticket-priority :priority="$t->priority" /></td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $t->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $t->is_active ? '启用' : '停用' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button @click="openEdit({{ json_encode($t->only(['id','name','subject','description','category_id','product_id','priority','is_active'])) }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">编辑</button>
                                    <form method="POST" action="{{ route('admin.ticket-templates.destroy', $t) }}" class="inline" onsubmit="return confirm('删除模板「{{ $t->name }}」？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-12 text-center text-gray-400">暂无工单模板，左侧创建</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">{{ $templates->links() }}</div>
        </div>
    </div>

    <script>
        function templateForm() {
            return {
                editing: false,
                form: { id: null, name: '', subject: '', description: '', category_id: '', product_id: '', priority: 'normal', is_active: true },
                openEdit(t) {
                    this.editing = true;
                    this.form = { ...t, category_id: t.category_id ?? '', product_id: t.product_id ?? '', is_active: !!t.is_active };
                    const form = this.$el.closest('form');
                    form.action = '{{ url(config('app.admin_url').'/ticket-templates') }}' + '/' + t.id;
                    form.insertAdjacentHTML('beforeend', '<input type="hidden" name="_method" value="PUT">');
                },
                reset() {
                    this.editing = false;
                    this.form = { id: null, name: '', subject: '', description: '', category_id: '', product_id: '', priority: 'normal', is_active: true };
                    const form = this.$el.closest('form');
                    form.action = '{{ route('admin.ticket-templates.store') }}';
                    form.querySelector('input[name="_method"]')?.remove();
                },
            };
        }
    </script>
@endsection
