@extends('layouts.app')

@section('page_title', '用户管理')

@section('content')
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
