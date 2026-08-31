@extends('layouts.app')

@section('page_title', '工单字段')

@section('content')
    <div x-data="fieldDefManager()">
        {{-- 工具栏 --}}
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                配置创建工单时的附加字段（文本 / 数字 / 下拉 / 日期），客户与客服都会填写。
            </p>
            <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                新增字段
            </button>
        </div>

        {{-- 列表 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">排序</th>
                        <th class="py-3 px-4">显示名</th>
                        <th class="py-3 px-4">字段 key</th>
                        <th class="py-3 px-4">类型</th>
                        <th class="py-3 px-4">选项</th>
                        <th class="py-3 px-4">必填</th>
                        <th class="py-3 px-4">状态</th>
                        <th class="py-3 px-4 text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($defs as $d)
                        <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="py-3 px-4 text-gray-400 font-mono text-xs">{{ $d->sort }}</td>
                            <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">{{ $d->label }}</td>
                            <td class="py-3 px-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $d->key }}</td>
                            <td class="py-3 px-4">
                                <span class="rounded-md bg-slate-100 dark:bg-slate-700/50 px-2 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-200">{{ \App\Models\TicketFieldDef::TYPES[$d->type] }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ $d->type === 'select' ? implode('、', $d->options ?? []) : '—' }}
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <span class="rounded-md px-2 py-0.5 font-medium ring-1 ring-inset {{ $d->is_required ? 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">{{ $d->is_required ? '必填' : '选填' }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <span class="rounded-md px-2 py-0.5 font-medium ring-1 ring-inset {{ $d->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">{{ $d->is_active ? '启用' : '停用' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <button type="button" @click="openEdit(@js($d->only(['id', 'label', 'key', 'type', 'options', 'is_required', 'is_active', 'sort'])))"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs mr-3">编辑</button>
                                <form method="POST" action="{{ route('admin.field-defs.destroy', $d) }}" class="inline" onsubmit="return confirm('删除后工单上的该字段值也会清除，确定？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs">删除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-gray-400 text-sm">暂无字段，点击右上角「新增字段」配置</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 新增/编辑弹窗 --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-16 bg-gray-900/50" @click.self="open = false">
            <div class="w-full max-w-lg rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="editing ? '编辑字段' : '新增字段'"></h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>
                <form :action="editing ? '/console/field-defs/' + editing.id : '{{ route('admin.field-defs.store') }}'" method="POST" class="px-5 py-4 space-y-4">
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PATCH">
                    </template>
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">显示名 <span class="text-red-500">*</span></label>
                            <input type="text" name="label" x-model="form.label" required maxlength="50" placeholder="如：设备序列号"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">字段 key（留空自动生成）</label>
                            <input type="text" name="key" x-model="form.key" maxlength="50" placeholder="如 serial_no"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">类型 <span class="text-red-500">*</span></label>
                            <select name="type" x-model="form.type"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="text">文本</option>
                                <option value="number">数字</option>
                                <option value="select">下拉选择</option>
                                <option value="date">日期</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">排序</label>
                            <input type="number" name="sort" x-model="form.sort" min="0" max="9999"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div x-show="form.type === 'select'">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">下拉选项（逗号分隔）</label>
                        <input type="text" name="options" x-model="form.optionsText" placeholder="如：硬件故障,软件问题,网络问题"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="flex items-center gap-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_required" value="1" x-model="form.is_required"
                                   class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">必填</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                   class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">启用</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">取消</button>
                        <button type="submit" class="rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 transition">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function fieldDefManager() {
                return {
                    open: false,
                    editing: null,
                    form: { label: '', key: '', type: 'text', optionsText: '', is_required: false, is_active: true, sort: 0 },

                    openCreate() {
                        this.editing = null;
                        this.form = { label: '', key: '', type: 'text', optionsText: '', is_required: false, is_active: true, sort: 0 };
                        this.open = true;
                    },
                    openEdit(d) {
                        this.editing = d;
                        this.form = {
                            label: d.label, key: d.key, type: d.type,
                            optionsText: (d.options || []).join(','),
                            is_required: !!d.is_required, is_active: !!d.is_active, sort: d.sort,
                        };
                        this.open = true;
                    },
                };
            }
        </script>
    @endpush
@endsection
