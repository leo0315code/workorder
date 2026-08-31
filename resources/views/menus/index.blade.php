@extends('layouts.app')

@section('page_title', '菜单管理')

@section('content')
    @php
        // @json() 内不能写带嵌套括号的表达式（会截断），先在这里组好数组
        $__menuRows = $menus->map(fn ($m) => $m->only(['id', 'label', 'route_name', 'audience', 'admin_only', 'icon', 'module', 'sort', 'is_active']))->values();
    @endphp
    <div x-data="menuManager()">
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

        {{-- 列表 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">排序</th>
                        <th class="py-3 px-4">所属端</th>
                        <th class="py-3 px-4">显示名</th>
                        <th class="py-3 px-4">路由名</th>
                        <th class="py-3 px-4">模块权限</th>
                        <th class="py-3 px-4">仅管理员</th>
                        <th class="py-3 px-4">状态</th>
                        <th class="py-3 px-4">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="m in menus" :key="m.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="py-3 px-4 text-gray-400 font-mono text-xs" x-text="m.sort"></td>
                            <td class="py-3 px-4">
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                      :class="m.audience === 'agent' ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 ring-indigo-200 dark:ring-indigo-500/30' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-emerald-200 dark:ring-emerald-500/30'"
                                      x-text="m.audience === 'agent' ? '客服端' : '客户端'"></span>
                            </td>
                            <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200" x-text="m.label"></td>
                            <td class="py-3 px-4 font-mono text-xs text-gray-500 dark:text-gray-400" x-text="m.route_name || '-'"></td>
                            <td class="py-3 px-4">
                                <span x-show="!m.module" class="text-xs text-gray-400">—</span>
                                <span x-show="m.module" class="rounded-md bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30" x-text="m.module"></span>
                            </td>
                            <td class="py-3 px-4 text-xs" x-text="m.admin_only ? '是' : '否'"></td>
                            <td class="py-3 px-4">
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                      :class="m.is_active ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300 ring-green-200 dark:ring-green-500/30' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 ring-gray-200 dark:ring-gray-700'"
                                      x-text="m.is_active ? '启用' : '停用'"></span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <button type="button" @click="openEdit(m)"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs mr-3">编辑</button>
                                <form method="POST" :action="'/console/menus/' + m.id" class="inline" onsubmit="return confirm('确定删除该菜单？');">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <button type="submit" class="text-red-500 hover:underline text-xs">删除</button>
                                </form>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p x-show="menus.length === 0" class="py-12 text-center text-gray-400 text-sm">暂无菜单</p>
        </div>

        {{-- 新增/编辑弹窗 --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-16 bg-gray-900/50" @click.self="open = false">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

    @push('scripts')
        <script>
            function menuManager() {
                return {
                    open: false,
                    editing: null,
                    menus: @json($__menuRows),
                    form: { label: '', route_name: '', audience: 'agent', admin_only: false, icon: 'ticket', module: '', sort: 0, is_active: true },

                    openCreate() {
                        this.editing = null;
                        this.form = { label: '', route_name: '', audience: 'agent', admin_only: false, icon: 'ticket', module: '', sort: 0, is_active: true };
                        this.open = true;
                    },
                    openEdit(m) {
                        this.editing = m;
                        this.form = {
                            label: m.label, route_name: m.route_name || '', audience: m.audience,
                            admin_only: !!m.admin_only, icon: m.icon, module: m.module || '',
                            sort: m.sort, is_active: !!m.is_active,
                        };
                        this.open = true;
                    },
                };
            }
        </script>
    @endpush
@endsection
