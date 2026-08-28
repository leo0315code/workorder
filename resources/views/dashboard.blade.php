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

    {{-- 最近工单（AJAX 局部刷新：点 tab 不整页刷新） --}}
    <x-panel id="recent" title="最近工单" icon="list" class="mt-6">
        <div id="recent-panel">
            @include('dashboard.partials.recent')
        </div>
    </x-panel>

    <script>
        (function () {
            // data-scope 按钮点击 → 局部刷新最近工单区块（不整页刷新）
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-scope]');
                if (! btn || ! window.__recentPanel) return;
                var scope = btn.getAttribute('data-scope');
                window.__recentPanel(scope);
            });
        })();

        function loadRecentPanel(scope) {
            var panel = document.getElementById('recent-panel');
            if (! panel) return;

            // 1) 即时切换 tab 高亮（不等待服务端返回）
            panel.querySelectorAll('[data-scope]').forEach(function (b) {
                var active = b.getAttribute('data-scope') === scope;
                b.className = active
                    ? 'inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium'
                    : 'inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer text-gray-500 dark:text-gray-400';
            });
            fetch('{{ url('dashboard/recent') }}?scope=' + encodeURIComponent(scope), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (! r.ok) throw new Error('fail'); return r.text(); })
                .then(function (html) {
                    panel.innerHTML = html;
                    history.replaceState(null, '', '?scope=' + scope + '#recent');
                })
                .catch(function () { window.location.href = '{{ route('dashboard') }}?scope=' + encodeURIComponent(scope); });
        }
        window.__recentPanel = loadRecentPanel;
    </script>

@endsection
