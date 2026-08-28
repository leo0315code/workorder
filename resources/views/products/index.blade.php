@extends('layouts.app')

@section('page_title', '产品管理')

@section('content')
    <div x-data="productModal()">
        <div class="mb-4">
            <button @click="openCreate()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                新建产品
            </button>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                            <th class="py-3 px-4">产品</th>
                            <th class="py-3 px-4">SKU</th>
                            <th class="py-3 px-4">保修期（天）</th>
                            <th class="py-3 px-4">工单数</th>
                            <th class="py-3 px-4">状态</th>
                            <th class="py-3 px-4 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $p)
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4">
                                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $p->name }}</p>
                                    <p class="text-xs text-gray-400 max-w-[280px] truncate">{{ $p->description }}</p>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $p->sku ?: '-' }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $p->warranty_days }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $p->tickets_count }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $p->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $p->is_active ? '启用' : '停用' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button @click="openEdit({{ $p->id }}, '{{ addslashes($p->name) }}', '{{ $p->sku }}', '{{ addslashes($p->description ?? '') }}', {{ $p->warranty_days }}, {{ $p->is_active ? 1 : 0 }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">编辑</button>
                                    <form method="POST" action="{{ route('admin.products.destroy', $p) }}" class="inline" onsubmit="return confirm('确定删除该产品？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-12 text-center text-gray-400">暂无产品</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">{{ $products->links() }}</div>
        </div>

        {{-- 弹窗 --}}
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50">
            <div class="w-full max-w-lg rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white" x-text="mode === 'create' ? '新建产品' : '编辑产品'"></h3>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>
                <form :action="mode === 'create' ? '{{ route('admin.products.store') }}' : updateUrl" method="POST" class="px-6 py-5 space-y-4">
                    <template x-if="mode === 'edit'">
                        <div><input type="hidden" name="_method" value="PUT"></div>
                    </template>
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">产品名称 <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="form.name" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">SKU</label>
                            <input type="text" name="sku" x-model="form.sku" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">保修天数</label>
                            <input type="number" name="warranty_days" x-model="form.warranty_days" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">描述</label>
                        <textarea name="description" x-model="form.description" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-gray-300 dark:border-gray-700"> 启用
                    </label>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false" class="text-sm text-gray-500 hover:text-gray-700">取消</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function productModal() {
            return {
                open: false,
                mode: 'create',
                updateUrl: '',
                form: { name: '', sku: '', description: '', warranty_days: 365, is_active: true },
                openCreate() {
                    this.mode = 'create';
                    this.updateUrl = '';
                    this.form = { name: '', sku: '', description: '', warranty_days: 365, is_active: true };
                    this.open = true;
                },
                openEdit(id, name, sku, description, days, active) {
                    this.mode = 'edit';
                    this.updateUrl = '{{ url(config('app.admin_url').'/products') }}' + '/' + id;
                    this.form = { name, sku, description, warranty_days: days, is_active: active === 1 };
                    this.open = true;
                },
            };
        }
    </script>
@endsection
