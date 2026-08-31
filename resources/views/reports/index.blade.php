@extends('layouts.app')

@section('page_title', '数据报表')

@section('content')
    {{-- 时间范围切换 --}}
    <div class="mb-5 flex items-center justify-between">
        <div class="flex items-center gap-2">
            @foreach ([7 => '近 7 天', 30 => '近 30 天', 90 => '近 90 天'] as $d => $label)
                <a href="{{ route('admin.reports', ['days' => $d]) }}"
                   class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $days === $d ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <a href="{{ route('admin.reports.export', ['days' => $days]) }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            导出 CSV
        </a>
    </div>

    {{-- 汇总 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-stat-card label="新增工单" :value="$summary['total']" icon="ticket" color="indigo" />
        <x-stat-card label="待处理" :value="$summary['open']" icon="clock" color="amber" />
        <x-stat-card label="已解决/关闭" :value="$summary['resolved']" icon="check" color="green" />
        <x-stat-card label="回复数" :value="$summary['replies']" icon="chat" color="sky" />
    </div>

    {{-- 满意度 --}}
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-stat-card label="平均满意度" :value="$ratingStats['avg'] === null ? '-' : $ratingStats['avg']" icon="star" color="amber" :hint="'共 '.$ratingStats['count'].' 次评价'" />
        <x-stat-card label="满意率（4★及以上）" :value="$ratingStats['positive_rate']" icon="check" color="green" :hint="'好评 '.$ratingStats['positive'].' 条'" />
        <x-stat-card label="问题已解决率" :value="$ratingStats['solved_rate']" icon="shield" color="sky" :hint="'解决 '.$ratingStats['solved'].'/'.$ratingStats['count'].' 条'" />
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- 每日新增趋势 --}}
        <x-panel title="每日新增工单趋势" class="xl:col-span-2">
            <div class="flex items-end gap-[3px] h-48">
                @php $max = max(1, max($dailySeries)); @endphp
                @foreach ($dailySeries as $i => $v)
                    <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                        <div class="w-full rounded-t bg-gradient-to-t from-indigo-600 to-violet-400 hover:from-indigo-500 hover:to-violet-300 transition"
                             style="height: {{ max(2, round($v / $max * 100)) }}%"></div>
                        @if ($v > 0)
                            <span class="absolute -top-1 text-[10px] text-gray-400 opacity-0 group-hover:opacity-100">{{ $v }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex gap-[3px] mt-1">
                @foreach ($dates as $i => $d)
                    <div class="flex-1 text-center text-[10px] text-gray-400 {{ $i % max(1, intdiv(count($dates), 8)) === 0 ? '' : 'opacity-0' }}">{{ $d }}</div>
                @endforeach
            </div>
        </x-panel>

        {{-- 状态/优先级分布 --}}
        <div class="space-y-6">
            <x-panel title="状态分布">
                @foreach (['open' => '待处理', 'pending' => '待客户', 'in_progress' => '处理中', 'resolved' => '已解决', 'closed' => '已关闭'] as $k => $label)
                    @php $c = $byStatus[$k] ?? 0; $max = max(1, $byStatus->max()); @endphp
                    <div class="flex items-center gap-3 mb-3 last:mb-0">
                        <span class="w-16 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full {{ $k === 'open' ? 'bg-amber-400' : ($k === 'in_progress' ? 'bg-sky-500' : ($k === 'resolved' ? 'bg-green-500' : ($k === 'closed' ? 'bg-gray-400' : 'bg-purple-400'))) }}"
                                 style="width: {{ round($c / $max * 100) }}%"></div>
                        </div>
                        <span class="w-8 text-right text-sm font-semibold">{{ $c }}</span>
                    </div>
                @endforeach
            </x-panel>

            <x-panel title="优先级分布">
                @foreach (['low' => '低', 'normal' => '普通', 'high' => '高', 'urgent' => '紧急'] as $k => $label)
                    @php $c = $byPriority[$k] ?? 0; $max = max(1, $byPriority->max()); @endphp
                    <div class="flex items-center gap-3 mb-3 last:mb-0">
                        <span class="w-16 text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full {{ $k === 'urgent' ? 'bg-red-500' : ($k === 'high' ? 'bg-orange-400' : ($k === 'normal' ? 'bg-sky-500' : 'bg-gray-300')) }}"
                                 style="width: {{ round($c / $max * 100) }}%"></div>
                        </div>
                        <span class="w-8 text-right text-sm font-semibold">{{ $c }}</span>
                    </div>
                @endforeach
            </x-panel>
        </div>
    </div>

    {{-- 客服处理排行 --}}
    <x-panel title="客服处理排行（本时段）" class="mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800">
                        <th class="py-2.5 pr-4">客服</th>
                        <th class="py-2.5 pr-4">处理工单数</th>
                        <th class="py-2.5 pr-4">回复数</th>
                        <th class="py-2.5 pr-4">平均首次响应</th>
                        <th class="py-2.5 pr-4">平均解决时长</th>
                        <th class="py-2.5">SLA 超时</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agents as $i => $a)
                        <tr class="border-b border-gray-100 dark:border-gray-800/60">
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2.5">
                                    @if ($i < 3)
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-xs font-bold text-white {{ $i === 0 ? 'bg-amber-400' : ($i === 1 ? 'bg-gray-400' : 'bg-orange-400') }}">{{ $i + 1 }}</span>
                                    @endif
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $a['name'] }}</span>
                                </div>
                            </td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $a['handled'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $a['replies'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                {{ $a['avg_first_response_hours'] === null ? '-' : $a['avg_first_response_hours'].' 小时' }}
                            </td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                {{ $a['avg_resolve_hours'] === null ? '-' : $a['avg_resolve_hours'].' 小时' }}
                            </td>
                            <td class="py-3 text-gray-600 dark:text-gray-300">
                                @if (($a['overdue'] ?? 0) > 0)
                                    <span class="inline-flex rounded-md bg-red-50 dark:bg-red-500/10 px-1.5 py-0.5 text-[11px] font-medium text-red-600 dark:text-red-300 ring-1 ring-inset ring-red-200 dark:ring-red-500/30">{{ $a['overdue'] }}</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-gray-400">暂无数据</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
@endsection
