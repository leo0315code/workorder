@extends('layouts.app')

@section('page_title', '用户管理')

@section('content')
    <div x-data="permEditor()">
    @if (request()->boolean('perm'))
        <div class="mb-4 rounded-lg border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50 dark:bg-indigo-500/10 px-4 py-3 text-sm text-indigo-700 dark:text-indigo-300">
            在下方每个客服/管理员的「<strong>模块权限</strong>」按钮中勾选其可见菜单与操作权限；未配置的客服默认拥有全部模块权限。
        </div>
    @endif
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="搜索姓名 / 邮箱"
               class="w-72 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
        <select name="role" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
            <option value="">全部角色</option>
            @foreach (\App\Models\User::ROLES as $k => $label)
                <option value="{{ $k }}" @selected(request('role') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-sm font-medium text-white dark:text-gray-900">筛选</button>
    </form>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">用户</th>
                        <th class="py-3 px-4">邮箱</th>
                        <th class="py-3 px-4">电话</th>
                        <th class="py-3 px-4">提交工单</th>
                        <th class="py-3 px-4">处理工单</th>
                        <th class="py-3 px-4">角色</th>
                        <th class="py-3 px-4">在线状态</th>
                        <th class="py-3 px-4">注册时间</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center text-xs font-semibold text-indigo-600 dark:text-indigo-300">
                                        {{ strtoupper(mb_substr($u->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $u->name }}</span>
                                    @if ($u->id === auth()->id())
                                        <span class="text-xs text-gray-400">（我）</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $u->email }}</td>
                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $u->phone ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $u->tickets_count }}</td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $u->assigned_tickets_count }}</td>
                            <td class="py-3 px-4">
                                @if ($u->id === auth()->id())
                                    <span class="text-gray-400">{{ \App\Models\User::ROLES[$u->role] }}</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.update-role', $u) }}">
                                        @csrf @method('PATCH')
                                        <select name="role" onchange="this.form.submit()"
                                                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                            @foreach (\App\Models\User::ROLES as $k => $label)
                                                <option value="{{ $k }}" @selected($u->role === $k)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                            @if ($u->isAgent())
                                <td class="py-3 px-4">
                                    @if ($u->isManuallyOffline())
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> 手动离线
                                        </span>
                                    @elseif (isset($onlineUids[$u->id]))
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-green-50 dark:bg-green-500/10 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300 ring-1 ring-inset ring-green-200 dark:ring-green-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> 在线
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span> 离线
                                        </span>
                                    @endif
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.toggle-offline', $u) }}" class="inline mt-1 block">
                                            @csrf
                                            <button type="submit" class="text-xs {{ $u->isManuallyOffline() ? 'text-green-600 dark:text-green-400' : 'text-red-500' }} hover:underline">
                                                {{ $u->isManuallyOffline() ? '恢复在线' : '设为离线' }}
                                            </button>
                                        </form>
                                        <button @click="openPerm({{ $u->id }}, '{{ $u->name }}', {{ json_encode($u->permissions) }})"
                                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-1 block">模块权限</button>
                                    @endif
                                </td>
                            @else
                                <td class="py-3 px-4 text-gray-400 text-xs">-</td>
                            @endif
                            <td class="py-3 px-4 text-gray-400 whitespace-nowrap">{{ $u->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">暂无用户</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">{{ $users->links() }}</div>
    </div>

    {{-- 模块权限弹层 --}}
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-cloak>
        <div class="w-full max-w-lg rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">模块权限 · <span x-text="name"></span></h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
            </div>

            <div class="mb-4 flex flex-wrap gap-2">
                <button type="button" @click="applyTemplate('full')"
                        class="rounded-lg bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30 hover:bg-indigo-100">普通客服（全部）</button>
                <button type="button" @click="applyTemplate('supervisor')"
                        class="rounded-lg bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30 hover:bg-indigo-100">主管（全部 + 管理）</button>
                <button type="button" @click="applyTemplate('readonly')"
                        class="rounded-lg bg-gray-100 dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-200 dark:ring-gray-700 hover:bg-gray-200">只读客服（看档案/产品/报表）</button>
            </div>

            <form method="POST" :action="'{{ url(config('app.admin_url').'/users') }}/' + id + '/permissions'" class="space-y-4">
                @csrf @method('PATCH')
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="mod in all" :key="mod">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer rounded-lg border border-gray-200 dark:border-gray-800 px-3 py-2">
                            <input type="checkbox" name="modules[]" :value="mod" x-model="modules" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span x-text="labels[mod]"></span>
                        </label>
                    </template>
                </div>
                <p class="text-xs text-gray-400">工单列表与处理是所有客服的基础能力；勾选决定「菜单显示 + 后端访问」。</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300">取消</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500">保存权限</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function permEditor() {
            return {
                open: false,
                id: null,
                name: '',
                modules: [],
                all: @json(array_keys(\App\Models\User::AGENT_MODULES)),
                labels: @json(\App\Models\User::AGENT_MODULES),
                openPerm(id, name, modules) {
                    this.id = id;
                    this.name = name;
                    this.modules = (modules && modules.length) ? [...modules] : [...this.all];
                    this.open = true;
                },
                applyTemplate(t) {
                    if (t === 'readonly') {
                        this.modules = ['customers', 'products', 'categories', 'reports'];
                    } else {
                        this.modules = [...this.all];
                    }
                },
            };
        }
    </script>
@endsection
