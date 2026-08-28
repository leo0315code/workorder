@php
    $isAgent = auth()->user()->isAgent();
    $scope = $scope ?? 'all';
    $scopeLabels = ['all' => '全部', 'open' => '待处理', 'resolved' => '已解决'];
    // 客户视图只传 byStatus/recent/total，数量兜底
    $total = $total ?? $recent->count();
    $openCount = $openCount ?? (($byStatus['open'] ?? 0) + ($byStatus['pending'] ?? 0) + ($byStatus['in_progress'] ?? 0));
    $resolvedCount = $resolvedCount ?? (($byStatus['resolved'] ?? 0) + ($byStatus['closed'] ?? 0));
    // 客户视图固定显示「全部」范围
    if (! $isAgent) { $scope = 'all'; }
@endphp

{{-- Tab 切换（AJAX 局部刷新） --}}
<div class="flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 p-1 mb-3 text-sm w-fit">
    <button type="button" data-scope="all"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer {{ $scope === 'all' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium' : 'text-gray-500 dark:text-gray-400' }}">全部<span class="text-xs opacity-70">({{ $total }})</span></button>
    <button type="button" data-scope="open"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer {{ $scope === 'open' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium' : 'text-gray-500 dark:text-gray-400' }}">待处理<span class="text-xs opacity-70">({{ $openCount }})</span></button>
    <button type="button" data-scope="resolved"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer {{ $scope === 'resolved' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium' : 'text-gray-500 dark:text-gray-400' }}">已解决<span class="text-xs opacity-70">({{ $resolvedCount }})</span></button>
</div>

{{-- 当前筛选状态条 --}}
<div class="mb-3 flex items-center gap-2 text-xs text-gray-400">
    <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-2 py-1 text-indigo-600 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 7.994 3Z" /></svg>
        当前：{{ $scopeLabels[$scope] }}（{{ $scope === 'open' ? $openCount : ($scope === 'resolved' ? $resolvedCount : $total) }} 条）
    </span>
    <span>显示最近 {{ $recent->count() }} 条</span>
</div>

@if ($recent->isEmpty())
    <p class="text-sm text-gray-400 py-6 text-center">
        @if ($scope === 'open') 当前没有待处理工单 🎉
        @elseif ($scope === 'resolved') 暂无已解决工单
        @else 暂无工单
        @endif
    </p>
@else
    {{-- 移动端卡片 --}}
    <div class="md:hidden space-y-3">
        @foreach ($recent as $t)
            <a href="{{ route('tickets.show', $t) }}" class="block rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm active:scale-[0.99] transition">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400">{{ $t->no }}</span>
                    <div class="flex items-center gap-1.5">
                        <x-ticket-priority :priority="$t->priority" />
                        @if ($t->isOverdue())
                            <span class="inline-flex rounded-md bg-red-50 dark:bg-red-500/10 px-1.5 py-0.5 text-[10px] font-medium text-red-600 dark:text-red-300 ring-1 ring-inset ring-red-200 dark:ring-red-500/30">超时</span>
                        @endif
                    </div>
                </div>
                <p class="mt-2 font-medium text-gray-900 dark:text-gray-100 line-clamp-2 leading-snug">{{ $t->subject }}</p>
                <div class="mt-2.5 flex items-center justify-between">
                    <span><x-ticket-status :status="$t->status" /></span>
                    <span class="text-xs text-gray-400">{{ $t->updated_at?->format('m-d H:i') }}</span>
                </div>
                <div class="mt-2 text-xs text-gray-400 flex items-center gap-2">
                    <span>{{ $t->user?->name ?? '—' }}</span>
                    <span>·</span>
                    <span class="ml-auto">{{ $t->assignee?->name ?? '待认领' }}</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- 桌面表格 --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800">
                    <th class="py-2.5 pr-4">编号</th>
                    <th class="py-2.5 pr-4">主题</th>
                    <th class="py-2.5 pr-4">状态</th>
                    <th class="py-2.5 pr-4">优先级</th>
                    @if ($isAgent)<th class="py-2.5 pr-4">客户</th>@endif
                    <th class="py-2.5 pr-4">负责人</th>
                    <th class="py-2.5">更新时间</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recent as $t)
                    <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                        <td class="py-3 pr-4 font-mono text-xs text-indigo-600 dark:text-indigo-400">
                            <a href="{{ route('tickets.show', $t) }}">{{ $t->no }}</a>
                        </td>
                        <td class="py-3 pr-4 max-w-[260px] truncate">
                            <a href="{{ route('tickets.show', $t) }}" class="hover:underline">{{ $t->subject }}</a>
                        </td>
                        <td class="py-3 pr-4"><x-ticket-status :status="$t->status" /></td>
                        <td class="py-3 pr-4"><x-ticket-priority :priority="$t->priority" /></td>
                        @if ($isAgent)
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-400">{{ $t->user?->name ?? '-' }}</td>
                        @endif
                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-400">{{ $t->assignee?->name ?? '-' }}</td>
                        <td class="py-3 text-gray-400">{{ $t->updated_at?->format('m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="mt-4">
    <a href="{{ route('tickets.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">查看全部工单 →</a>
</div>
