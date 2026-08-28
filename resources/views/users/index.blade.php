@extends('layouts.app')

@section('page_title', '用户管理')

@section('content')
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
@endsection
