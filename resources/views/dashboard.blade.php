@extends('layouts.app')

@section('page_title', '仪表盘')

@php $isAgent = auth()->user()->isAgent(); @endphp

@section('content')
    {{-- 统计卡片 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-stat-card label="工单总数" :value="$total" icon="ticket" color="indigo" />
        @if ($isAgent)
            <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="block">
                <x-stat-card label="待处理" :value="$open" icon="clock" color="amber" />
            </a>
            <x-stat-card label="今日已解决" :value="$resolvedToday" icon="check" color="green" />
            <a href="{{ route('tickets.index', ['overdue' => 1]) }}" class="block">
                <x-stat-card label="SLA 超时" :value="$overdue" icon="alert" color="red" />
            </a>
        @else
            <x-stat-card label="处理中" :value="$open" icon="clock" color="amber" />
            <x-stat-card label="已解决/关闭" :value="$resolved" icon="check" color="green" />
            <x-stat-card label="我的工单" :value="$total" icon="ticket" color="indigo" />
        @endif
    </div>

    @if ($isAgent)
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('tickets.index', ['mine' => 1]) }}" class="block">
                <x-stat-card label="指派给我(未完成)" :value="$myOpen" icon="user" color="sky" />
            </a>
            <a href="{{ route('tickets.index', ['unassigned' => 1]) }}" class="block">
                <x-stat-card label="待认领" :value="$unassigned" icon="alert" color="orange" />
            </a>
            <div class="col-span-2 flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-4">
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-600 dark:text-green-400">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> 实时推送已接入（GatewayWorker）
                </span>
                <span class="text-xs text-gray-400">监听 ws://127.0.0.1:6001</span>
            </div>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- 状态分布 --}}
        <x-panel title="状态分布" icon="chart">
            @foreach (['open' => '待处理', 'pending' => '待客户', 'in_progress' => '处理中', 'resolved' => '已解决', 'closed' => '已关闭'] as $key => $label)
                @php $c = $byStatus[$key] ?? 0; $max = max(1, $byStatus->max()); @endphp
                <div class="flex items-center gap-3 mb-3 last:mb-0">
                    <span class="w-16 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                    <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full rounded-full {{ $key === 'open' ? 'bg-amber-400' : ($key === 'in_progress' ? 'bg-sky-500' : ($key === 'resolved' ? 'bg-green-500' : ($key === 'closed' ? 'bg-gray-400' : 'bg-purple-400'))) }}"
                             style="width: {{ round($c / $max * 100) }}%"></div>
                    </div>
                    <span class="w-8 text-right text-sm font-semibold">{{ $c }}</span>
                </div>
            @endforeach
        </x-panel>

        {{-- 优先级分布 --}}
        <x-panel title="优先级分布" icon="chart">
            @foreach (['low' => '低', 'normal' => '普通', 'high' => '高', 'urgent' => '紧急'] as $key => $label)
                @php $c = $byPriority[$key] ?? 0; $max = max(1, $byPriority->max()); @endphp
                <div class="flex items-center gap-3 mb-3 last:mb-0">
                    <span class="w-16 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                    <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full rounded-full {{ $key === 'urgent' ? 'bg-red-500' : ($key === 'high' ? 'bg-orange-400' : ($key === 'normal' ? 'bg-sky-500' : 'bg-gray-300')) }}"
                             style="width: {{ round($c / $max * 100) }}%"></div>
                    </div>
                    <span class="w-8 text-right text-sm font-semibold">{{ $c }}</span>
                </div>
            @endforeach
        </x-panel>

        {{-- 分类分布（客服）--}}
        @if ($isAgent)
            <x-panel title="分类分布" icon="chart">
                @foreach ($byCategory as $name => $c)
                    <div class="flex items-center gap-3 mb-2 last:mb-0">
                        <span class="w-24 truncate text-sm text-gray-600 dark:text-gray-400">{{ $name }}</span>
                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ round($c / max(1, $byCategory->max()) * 100) }}%"></div>
                        </div>
                        <span class="w-8 text-right text-sm font-semibold">{{ $c }}</span>
                    </div>
                @endforeach
                @if ($byCategory->isEmpty())
                    <p class="text-sm text-gray-400">暂无数据</p>
                @endif
            </x-panel>
        @endif
    </div>

    {{-- 最近工单 --}}
    <x-panel title="最近工单" icon="list" class="mt-6">
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 p-1 mb-4 text-sm w-fit">
            <button type="submit" name="scope" value="all"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer {{ ($scope ?? 'all') === 'all' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium' : 'text-gray-500 dark:text-gray-400' }}">全部<span class="text-xs opacity-70">({{ $total }})</span></button>
            <button type="submit" name="scope" value="open"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer {{ ($scope ?? 'all') === 'open' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium' : 'text-gray-500 dark:text-gray-400' }}">待处理<span class="text-xs opacity-70">({{ $byStatus['open'] ?? 0 }}+{{ $byStatus['pending'] ?? 0 }}+{{ $byStatus['in_progress'] ?? 0 }})</span></button>
            <button type="submit" name="scope" value="resolved"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer {{ ($scope ?? 'all') === 'resolved' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium' : 'text-gray-500 dark:text-gray-400' }}">已解决<span class="text-xs opacity-70">({{ ($byStatus['resolved'] ?? 0) + ($byStatus['closed'] ?? 0) }})</span></button>
        </form>
        @if ($recent->isEmpty())
            <p class="text-sm text-gray-400 py-6 text-center">
                @if (($scope ?? 'all') === 'open') 当前没有待处理工单 🎉
                @elseif (($scope ?? 'all') === 'resolved') 暂无已解决工单
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
    </x-panel>
@endsection
