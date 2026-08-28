@extends('layouts.app')

@section('page_title', '客服角色')

@section('content')
    @php
        $moduleIcons = [
            'customers' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />',
            'products' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />',
            'categories' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />',
            'quick-replies' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />',
            'templates' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />',
            'reports' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
            'export' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />',
            'batch' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />',
        ];
        $moduleColors = [
            'customers' => 'sky', 'products' => 'amber', 'categories' => 'emerald',
            'quick-replies' => 'indigo', 'templates' => 'indigo', 'reports' => 'rose',
            'export' => 'orange', 'batch' => 'red',
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 表单 --}}
        <div x-data="roleForm()">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-5 shadow-sm">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100" x-text="editing ? '编辑角色' : '新建角色'"></h3>
                </div>

                <form method="POST" action="{{ route('admin.agent-roles.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            <span>标识名</span>
                            <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-gray-400 ml-1">（代码内识别）</span>
                        </label>
                        <input type="text" name="name" required maxlength="50" pattern="[a-z0-9_]+" x-model="form.name" placeholder="supervisor / reader / handler"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono">
                        <p class="mt-1.5 text-xs text-gray-400">小写字母 + 数字 + 下划线，如：supervisor / standard / reader</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">显示名 <span class="text-red-500">*</span></label>
                        <input type="text" name="label" required maxlength="50" x-model="form.label" placeholder="如：客服主管 / 普通客服 / 档案客服"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">说明</label>
                        <textarea name="description" rows="2" maxlength="255" x-model="form.description" placeholder="该角色的职责与适用场景"
                                  class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            授权模块 <span class="text-red-500">*</span>
                            <span class="ml-2 text-xs font-normal text-gray-400">（决定该角色可见的后台菜单与操作）</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (\App\Models\User::AGENT_MODULES as $key => $label)
                                <label class="group flex items-center gap-2.5 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-500/40 hover:bg-indigo-50/40 dark:hover:bg-indigo-500/5 cursor-pointer transition">
                                    <input type="checkbox" name="modules[]" value="{{ $key }}" x-model="form.modules"
                                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">排序</label>
                            <input type="number" name="sort" min="0" x-model="form.sort"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800/50 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                       class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                启用
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" x-show="editing" @click="reset()"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">取消</button>
                        <button type="submit"
                                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                x-text="editing ? '保存修改' : '创建角色'"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 列表 --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-900/60">
                            <th class="py-3 px-4 font-medium">角色</th>
                            <th class="py-3 px-4 font-medium">授权模块</th>
                            <th class="py-3 px-4 font-medium text-center">用户</th>
                            <th class="py-3 px-4 font-medium">状态</th>
                            <th class="py-3 px-4 font-medium text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $r)
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $r->label }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5 font-mono">{{ $r->name }}@if($r->description) · {{ $r->description }}@endif</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach (($r->modules ?? []) as $m)
                                            @php $c = $moduleColors[$m] ?? 'indigo'; @endphp
                                            <span class="inline-flex items-center gap-1 rounded-md bg-{{ $c }}-50 dark:bg-{{ $c }}-500/10 px-2 py-0.5 text-[11px] font-medium text-{{ $c }}-700 dark:text-{{ $c }}-300 ring-1 ring-inset ring-{{ $c }}-200 dark:ring-{{ $c }}-500/30">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">{!! $moduleIcons[$m] ?? '' !!}</svg>
                                                {{ \App\Models\User::AGENT_MODULES[$m] ?? $m }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-md bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $r->users_count }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $r->is_active ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30' : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $r->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        {{ $r->is_active ? '启用' : '停用' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button @click="openEdit({{ json_encode($r->only(['id','name','label','description','modules','sort','is_active'])) }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3 text-sm">编辑</button>
                                    <form method="POST" action="{{ route('admin.agent-roles.destroy', $r) }}" class="inline" onsubmit="return confirm('删除角色「{{ $r->label }}」？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline text-sm">删除</button>
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
