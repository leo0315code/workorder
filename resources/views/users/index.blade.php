@extends('layouts.app')

@section('page_title', '用户管理')

@section('content')
    <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
        {{-- 工具栏：筛选 + 新增 --}}
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-1 flex-col sm:flex-row gap-2">
                <select id="filter-role" name="role" onchange="this.form.submit()"
                        class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">全部角色</option>
                    @foreach (\App\Models\User::ROLES as $k => $label)
                        <option value="{{ $k }}" @selected(request('role') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="relative flex-1 sm:max-w-xs">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="搜索姓名 / 邮箱"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 pl-8 pr-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">搜索</button>
                @if (request()->has('role') || request()->filled('q'))
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 transition">重置</a>
                @endif
            </form>

            <button type="button" @click="open = true"
                    class="shrink-0 inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-500/35 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                新增用户
            </button>
        </div>

        {{-- 新增用户弹窗 --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-16 bg-gray-900/50" @click.self="open = false">
            <div class="w-full max-w-lg rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl"
                 x-data="{ role: '{{ old('role', 'agent') }}' }">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">新增用户</h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.users.store') }}" class="px-5 py-4 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">姓名 <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="50"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">手机号</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="11" placeholder="选填"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('phone')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">邮箱</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <p class="-mt-1 text-xs text-gray-400">邮箱可留空；邮箱与手机号至少填一个，只有手机号的用户可通过短信验证码登录。</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">密码 <span class="text-red-500">*</span></label>
                            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('password')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">确认密码 <span class="text-red-500">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <p class="text-xs text-gray-400">密码至少 8 位。账号创建后即为已验证状态，可直接登录。</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="role" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">系统角色 <span class="text-red-500">*</span></label>
                            <select id="role" name="role" x-model="role"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach (\App\Models\User::ROLES as $k => $label)
                                    <option value="{{ $k }}" @selected(old('role', 'agent') === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('role')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        </div>
                        <div x-show="role !== 'customer'">
                            <label for="agent_role_id" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">客服角色</label>
                            <select id="agent_role_id" name="agent_role_id"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">默认（全模块）</option>
                                @foreach ($agentRoles as $r)
                                    <option value="{{ $r->id }}" @selected(old('agent_role_id') == $r->id)>{{ $r->label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">在 <a href="{{ route('admin.agent-roles.index') }}" class="text-indigo-600 hover:underline">角色管理</a> 中维护模板</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false"
                                class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            取消
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition">
                            创建
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                        <th class="py-3 px-4">系统角色</th>
                        <th class="py-3 px-4">客服角色</th>
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
                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $u->email ?: '-' }}</td>
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
                            <td class="py-3 px-4">
                                @if ($u->role === 'customer')
                                    <span class="text-xs text-gray-400">-</span>
                                @elseif ($u->id === auth()->id())
                                    <span class="text-xs text-gray-400">不可改自己</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.update-agent-role', $u) }}">
                                        @csrf @method('PATCH')
                                        <select name="agent_role_id" onchange="this.form.submit()"
                                                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                            <option value="">默认（全模块）</option>
                                            @foreach ($agentRoles as $r)
                                                <option value="{{ $r->id }}" @selected($u->agent_role_id === $r->id)>{{ $r->label }}</option>
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
                                    @endif
                                </td>
                            @else
                                <td class="py-3 px-4 text-gray-400 text-xs">-</td>
                            @endif
                            <td class="py-3 px-4 text-gray-400 whitespace-nowrap">{{ $u->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-12 text-center text-gray-400">暂无用户</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">{{ $users->links() }}</div>
    </div>

    <p class="mt-3 text-xs text-gray-400">系统角色决定顶级权限（客户/客服/管理员）；客服角色细化每个客服可见的模块。在 <a href="{{ route('admin.agent-roles.index') }}" class="text-indigo-600 hover:underline">角色管理</a> 中维护客服角色模板。</p>
@endsection
