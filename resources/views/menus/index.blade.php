@extends('layouts.app')

@section('page_title', '菜单管理')

@section('content')
    @php
        // 服务端预展平：分组标题 + 普通行交替，单层 <tr> 循环即可
        $__menuRows = $menus->map(fn ($m) => $m->only(['id', 'label', 'route_name', 'audience', 'admin_only', 'icon', 'module', 'section', 'sort', 'is_active']))->values();
        $__flatRows = [];
        $__prevSection = null;
        foreach ($__menuRows as $__row) {
            $__sec = $__row['section'] ?? '';
            if ($__sec !== '' && $__sec !== $__prevSection) {
                $__flatRows[] = ['is_group_header' => true, 'section' => $__sec, 'count' => $__menuRows->where('section', $__sec)->count()];
            }
            $__prevSection = $__sec;
            $__flatRows[] = ['is_group_header' => false] + $__row;
        }
        $__csrf = csrf_token();
    @endphp

    <div x-data="{
        open: false,
        editing: null,
        form: { label: '', route_name: '', audience: 'agent', admin_only: false, icon: 'ticket', module: '', section: '', sort: 0, is_active: true },
        openCreate() {
            this.editing = null;
            this.form = { label: '', route_name: '', audience: 'agent', admin_only: false, icon: 'ticket', module: '', section: '', sort: 0, is_active: true };
            this.open = true;
        },
        openEdit(r) {
            this.editing = r;
            this.form = {
                label: r.label, route_name: r.route_name || '', audience: r.audience,
                admin_only: !!r.admin_only, icon: r.icon, module: r.module || '', section: r.section || '',
                sort: r.sort, is_active: !!r.is_active,
            };
            this.open = true;
        },
    }" x-cloak>

        {{-- 工具栏 --}}
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    侧边栏菜单由数据库驱动，路由与权限仍在代码侧（路由名不存在时自动隐藏）。
                </p>
            </div>
            <button type="button" @click="openCreate()"
                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                新增菜单
            </button>
        </div>

        {{-- 列表（服务端渲染，避免 Alpine 解析大 JSON 失败）--}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">排序</th>
                        <th class="py-3 px-4">所属端</th>
                        <th class="py-3 px-4">显示名</th>
                        <th class="py-3 px-4">分组</th>
                        <th class="py-3 px-4">路由名</th>
                        <th class="py-3 px-4">模块权限</th>
                        <th class="py-3 px-4">仅管理员</th>
                        <th class="py-3 px-4">状态</th>
                        <th class="py-3 px-4">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($__flatRows as $__r)
                        @if ($__r['is_group_header'] ?? false)
                            <tr class="bg-gray-50/60 dark:bg-gray-900/40">
                                <td colspan="9" class="px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ $__r['section'] }}<span class="text-gray-400 font-normal"> · {{ $__r['count'] }} 项</span>
                                </td>
                            </tr>
                        @else
                            @php
                                $__id = $__r['id'];
                                $__updateUrl = route('admin.menus.update-field', ['menu' => $__id]);
                                $__delUrl = route('admin.menus.destroy', ['menu' => $__id]);
                                $__editUrl = route('admin.menus.update', ['menu' => $__id]);
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4 text-gray-400 font-mono text-xs">{{ $__r['sort'] }}</td>

                                {{-- 所属端：内联下拉 --}}
                                <td class="py-3 px-4">
                                    <form method="POST" action="{{ $__updateUrl }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="field" value="audience">
                                        <select name="value" onchange="this.form.submit()"
                                                class="rounded-md pl-2 pr-6 py-0.5 text-xs font-medium ring-1 ring-inset appearance-none cursor-pointer
                                                       {{ ($__r['audience'] ?? '') === 'agent'
                                                            ? 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30'
                                                            : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30' }}">
                                            <option value="agent" @selected(($__r['audience'] ?? '') === 'agent')>客服端</option>
                                            <option value="customer" @selected(($__r['audience'] ?? '') === 'customer')>客户端</option>
                                        </select>
                                    </form>
                                </td>

                                <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">{{ $__r['label'] }}</td>

                                <td class="py-3 px-4">
                                    @if (! empty($__r['section']))
                                        <span class="rounded-md bg-slate-100 dark:bg-slate-700/50 px-2 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-200">{{ $__r['section'] }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="py-3 px-4 font-mono text-xs text-gray-500 dark:text-gray-400 truncate">{{ $__r['route_name'] ?: '—' }}</td>

                                <td class="py-3 px-4">
                                    @if (! empty($__r['module']))
                                        <span class="rounded-md bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30">{{ $__r['module'] }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- 仅管理员：内联下拉 --}}
                                <td class="py-3 px-4">
                                    <form method="POST" action="{{ $__updateUrl }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="field" value="admin_only">
                                        <select name="value" onchange="this.form.submit()"
                                                class="rounded-md pl-2 pr-6 py-0.5 text-xs font-medium ring-1 ring-inset appearance-none cursor-pointer
                                                       {{ $__r['admin_only']
                                                            ? 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30'
                                                            : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">
                                            <option value="1" @selected($__r['admin_only'])>是</option>
                                            <option value="0" @selected(! $__r['admin_only'])>否</option>
                                        </select>
                                    </form>
                                </td>

                                {{-- 状态：内联下拉 --}}
                                <td class="py-3 px-4">
                                    <form method="POST" action="{{ $__updateUrl }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="field" value="is_active">
                                        <select name="value" onchange="this.form.submit()"
                                                class="rounded-md pl-2 pr-6 py-0.5 text-xs font-medium ring-1 ring-inset appearance-none cursor-pointer
                                                       {{ $__r['is_active']
                                                            ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30'
                                                            : 'bg-gray-100 text-gray-500 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">
                                            <option value="1" @selected($__r['is_active'])>启用</option>
                                            <option value="0" @selected(! $__r['is_active'])>停用</option>
                                        </select>
                                    </form>
                                </td>

                                <td class="py-3 px-4 whitespace-nowrap">
                                    <button type="button"
                                            @click="openEdit(@js($__r))"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs mr-3">编辑</button>
                                    <form method="POST" action="{{ $__delUrl }}" class="inline" onsubmit="return confirm('确定删除该菜单？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline text-xs">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="9" class="py-12 text-center text-gray-400 text-sm">暂无菜单</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 新增/编辑弹窗（Alpine 仅控制开/关）--}}
        <div x-show="open" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-16 bg-gray-900/50" @click.self="open = false">
            <div class="w-full max-w-lg rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="editing ? '编辑菜单' : '新增菜单'"></h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>

                <form :action="editing ? '/console/menus/' + editing.id : '{{ route('admin.menus.store') }}'" method="POST" class="px-5 py-4 space-y-4">
                    <template x-if="editing">
                        <input type="hidden" name="_method" value="PATCH">
                    </template>
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">显示名 <span class="text-red-500">*</span></label>
                            <input type="text" name="label" x-model="form.label" required maxlength="50"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">所属端 <span class="text-red-500">*</span></label>
                            <select name="audience" x-model="form.audience"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="agent">客服端</option>
                                <option value="customer">客户端</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">路由名</label>
                        <input type="text" name="route_name" x-model="form.route_name" placeholder="如 admin.customers.index（留空=仅展示不可点）"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-xs text-gray-400">必须指向已注册的路由，否则侧栏自动隐藏该菜单</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">图标</label>
                            <select name="icon" x-model="form.icon"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="dashboard">仪表盘</option>
                                <option value="ticket">工单</option>
                                <option value="customer">客户</option>
                                <option value="product">产品</option>
                                <option value="category">分类</option>
                                <option value="reply">回复</option>
                                <option value="chart">报表</option>
                                <option value="user">用户</option>
                                <option value="shield">权限</option>
                                <option value="gear">设置</option>
                                <option value="list">列表</option>
                                <option value="clock">时钟</option>
                                <option value="check">勾选</option>
                                <option value="alert">警告</option>
                                <option value="chat">对话</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">模块权限</label>
                            <select name="module" x-model="form.module"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">不限（所有客服可见）</option>
                                @foreach ($modules as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">分组（侧栏小标题）</label>
                            <input type="text" name="section" x-model="form.section" maxlength="30" placeholder="如：概览 / 业务数据 / 系统管理"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">排序</label>
                            <input type="number" name="sort" x-model="form.sort" min="0" max="9999"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="admin_only" value="1" x-model="form.admin_only"
                                       class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">仅管理员可见</span>
                            </label>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                       class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">启用</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">取消</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection