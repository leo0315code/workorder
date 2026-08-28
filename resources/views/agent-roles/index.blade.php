@extends('layouts.app')

@section('page_title', '客服角色')

@section('content')
    @php $allModules = \App\Models\User::AGENT_MODULES; @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 表单 --}}
        <div x-data="roleForm()">
            <form method="POST" action="{{ route('admin.agent-roles.store') }}" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4">
                @csrf
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="editing ? '编辑角色' : '新建角色'"></h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">标识名 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="50" pattern="[a-z0-9_]+" x-model="form.name" placeholder="如：supervisor / reader"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    <p class="mt-1 text-xs text-gray-400">小写字母+数字+下划线（代码内识别）</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">显示名 <span class="text-red-500">*</span></label>
                    <input type="text" name="label" required maxlength="50" x-model="form.label" placeholder="如：客服主管 / 只读客服"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">说明</label>
                    <input type="text" name="description" maxlength="255" x-model="form.description" placeholder="角色职责描述（可选）"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">授权模块 <span class="text-red-500">*</span></label>
                    <div class="space-y-1.5">
                        @foreach ($allModules as $key => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="modules[]" value="{{ $key }}" x-model="form.modules" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">排序</label>
                        <input type="number" name="sort" min="0" x-model="form.sort" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600">
                            启用
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" x-show="editing" @click="reset()" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300">取消</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500" x-text="editing ? '保存修改' : '创建角色'"></button>
                </div>
            </form>
        </div>

        {{-- 列表 --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                            <th class="py-3 px-4">角色</th>
                            <th class="py-3 px-4">模块</th>
                            <th class="py-3 px-4">用户</th>
                            <th class="py-3 px-4">状态</th>
                            <th class="py-3 px-4 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $r)
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $r->label }}</div>
                                    <div class="text-xs text-gray-400">{{ $r->name }} @if($r->description)· {{ $r->description }} @endif</div>
                                </td>
                                <td class="py-3 px-4 max-w-[260px]">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (($r->modules ?? []) as $m)
                                            <span class="inline-flex rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30">{{ $allModules[$m] ?? $m }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $r->users_count }} 人</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $r->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $r->is_active ? '启用' : '停用' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button @click="openEdit({{ json_encode($r->only(['id','name','label','description','modules','sort','is_active'])) }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">编辑</button>
                                    <form method="POST" action="{{ route('admin.agent-roles.destroy', $r) }}" class="inline" onsubmit="return confirm('删除角色「{{ $r->label }}」？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center text-gray-400">暂无角色，左侧创建</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">{{ $roles->links() }}</div>
        </div>
    </div>

    <script>
        function roleForm() {
            return {
                editing: false,
                form: { id: null, name: '', label: '', description: '', modules: [], sort: 0, is_active: true },
                openEdit(r) {
                    this.editing = true;
                    this.form = { ...r, modules: Array.isArray(r.modules) ? [...r.modules] : [], is_active: !!r.is_active };
                    const form = this.$el.closest('form');
                    form.action = '{{ url(config('app.admin_url').'/agent-roles') }}' + '/' + r.id;
                    form.insertAdjacentHTML('beforeend', '<input type="hidden" name="_method" value="PUT">');
                },
                reset() {
                    this.editing = false;
                    this.form = { id: null, name: '', label: '', description: '', modules: [], sort: 0, is_active: true };
                    const form = this.$el.closest('form');
                    form.action = '{{ route('admin.agent-roles.store') }}';
                    form.querySelector('input[name="_method"]')?.remove();
                },
            };
        }
    </script>
@endsection
