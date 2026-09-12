@extends('layouts.app')

@section('page_title', '操作审计')

@section('content')
    <div class="space-y-4">
        {{-- 页头 --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-1 h-6 rounded-full bg-gradient-to-b from-indigo-500 to-violet-500 inline-block"></span>
                    工单操作审计
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">跨工单检索全部操作日志（创建/回复/备注/状态与字段变更），用于追责与合规排查</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                共 {{ $logs->total() }} 条记录
            </span>
        </div>

        {{-- 筛选 --}}
        <form method="GET" action="{{ route('admin.ticket-logs.index') }}" class="flex flex-wrap items-center gap-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 shadow-sm">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="工单编号 / 主题 / 备注…"
                   class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 w-56">
            <select name="user_id" class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 px-3 py-2">
                <option value="">全部操作人</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}（{{ $u->role }}）</option>
                @endforeach
            </select>
            <select name="action" class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 px-3 py-2">
                <option value="">全部动作</option>
                @foreach (\App\Models\TicketLog::DESCRIPTIONS as $k => $v)
                    <option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" title="开始日期"
                   class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 px-3 py-2">
            <span class="text-sm text-gray-400">至</span>
            <input type="date" name="to" value="{{ request('to') }}" title="结束日期"
                   class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 px-3 py-2">
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-indigo-600 hover:to-violet-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                筛选
            </button>
            @if (request()->anyFilled(['q', 'user_id', 'action', 'from', 'to']))
                <a href="{{ route('admin.ticket-logs.index') }}" class="text-sm text-gray-500 hover:text-indigo-500 transition">清除筛选</a>
            @endif
        </form>

        {{-- 列表 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[860px] divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">时间</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">操作人</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">工单</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">动作</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">变更明细</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">备注</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $log->user?->name ?? '系统' }}</span>
                                    @if ($log->user)
                                        <span class="ml-1 text-[11px] text-gray-400">{{ $log->user->role }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if ($log->ticket)
                                        <a href="{{ route('admin.tickets.show', $log->ticket) }}"
                                           class="font-mono text-xs text-indigo-600 dark:text-indigo-400 hover:underline" title="{{ $log->ticket->subject }}">
                                            {{ $log->ticket->no }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @php
                                        $actionMap = [
                                            'created' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-emerald-200 dark:ring-emerald-500/30',
                                            'replied' => 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300 ring-sky-200 dark:ring-sky-500/30',
                                            'noted'   => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 ring-amber-200 dark:ring-amber-500/30',
                                            'closed'  => 'bg-gray-100 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300 ring-gray-200 dark:ring-gray-600/40',
                                            'reopened' => 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 ring-purple-200 dark:ring-purple-500/30',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $actionMap[$log->action] ?? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 ring-indigo-200 dark:ring-indigo-500/30' }}">
                                        {{ $log->action === 'change' ? '变更' : (\App\Models\TicketLog::DESCRIPTIONS[$log->action] ?? $log->action) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 max-w-[260px]">
                                    @if ($log->action === 'change' && $log->field)
                                        <p class="text-xs text-gray-600 dark:text-gray-300">
                                            <span class="text-gray-400">{{ $log->field }}</span>：
                                            <span class="text-gray-400 line-through">{{ $log->old_value ?: '空' }}</span>
                                            → <span class="font-medium">{{ $log->new_value }}</span>
                                        </p>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 max-w-[200px]">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $log->note }}">{{ $log->note ?: '—' }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">暂无操作日志</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
