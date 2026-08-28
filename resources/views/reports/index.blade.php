@extends('layouts.app')

@section('page_title', '数据报表')

@section('content')
    {{-- 时间范围切换 --}}
    <div class="mb-5 flex items-center gap-2">
        @foreach ([7 => '近 7 天', 30 => '近 30 天', 90 => '近 90 天'] as $d => $label)
            <a href="{{ route('admin.reports', ['days' => $d]) }}"
               class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $days === $d ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                {{ $label }}
            </a>
        @endforeach
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
        <div class="flex items-center gap-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-4">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
            </span>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ratingStats['avg'] === null ? '-' : $ratingStats['avg'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">平均满意度（{{ $ratingStats['count'] }} 次评价）</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-5 py-4">
            <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </span>
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $ratingStats['positive_rate'] }}<span class="text-base text-gray-400">%</span></p>
                <p class="text-xs text-gray-400 mt-0.5">满意率（4★及以上，{{ $ratingStats['positive'] }} 条）</p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- 每日新增趋势 --}}
        <x-panel title="每日新增工单趋势" class="xl:col-span-2">
            <div class="flex items-end gap-[3px] h-48">
                @php $max = max(1, max($dailySeries)); @endphp
                @foreach ($dailySeries as $i => $v)
                    <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                        <div class="w-full rounded-t bg-indigo-500/80 hover:bg-indigo-500 transition"
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
                        <th class="py-2.5">平均首次响应时长</th>
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
                            <td class="py-3 text-gray-600 dark:text-gray-300">
                                {{ $a['avg_first_response_hours'] === null ? '-' : $a['avg_first_response_hours'].' 小时' }}
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
