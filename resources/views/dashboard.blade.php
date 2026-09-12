@extends('layouts.app')

@section('page_title', '仪表盘')

@php
    $isAgent = auth()->user()->isAgent();
    $user = auth()->user();
    // 问候语（按小时）与中文日期
    $hour = (int) now()->format('H');
    $greeting = $hour < 6 ? '夜深了' : ($hour < 12 ? '早上好' : ($hour < 18 ? '下午好' : '晚上好'));
    $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
    $dateText = now()->format('Y年n月j日').' 星期'.$weekdays[now()->dayOfWeek];
@endphp

@section('content')
    {{-- 欢迎横幅 --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 shadow-lg shadow-indigo-500/25 px-4 sm:px-6 py-5 sm:py-6 mb-5 sm:mb-6">
        {{-- 装饰圆 --}}
        <div class="pointer-events-none absolute -top-16 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 right-1/3 w-44 h-44 rounded-full bg-white/5 blur-xl"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-indigo-200">{{ $dateText }}</p>
                <h1 class="mt-1 text-2xl font-bold text-white">{{ $greeting }}，{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-indigo-100/90">
                    @if ($isAgent)
                        有 {{ $open }} 个工单待处理，{{ $overdue }} 个已超时，记得优先处理哦
                    @else
                        随时提交工单，我们会第一时间响应
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                @if ($isAgent)
                    <a href="{{ ticket_route('create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        新建工单
                    </a>
                    <a href="{{ ticket_route('index', ['status' => 'open']) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 hover:bg-white/20 transition">
                        待处理列表
                    </a>
                @else
                    <a href="{{ ticket_route('create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        提交工单
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- 统计卡片 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <x-stat-card label="工单总数" :value="$total" icon="ticket" color="indigo" />
        @if ($isAgent)
            <a href="{{ ticket_route('index', ['status' => 'open']) }}" class="block">
                <x-stat-card label="待处理" :value="$open" icon="clock" color="amber" />
            </a>
            <x-stat-card label="今日已解决" :value="$resolvedToday" icon="check" color="green" />
            <a href="{{ ticket_route('index', ['overdue' => 1]) }}" class="block">
                <x-stat-card label="SLA 超时" :value="$overdue" icon="alert" color="red" />
            </a>
        @else
            <x-stat-card label="处理中" :value="$open" icon="clock" color="amber" />
            <x-stat-card label="已解决/关闭" :value="$resolved" icon="check" color="green" />
            <x-stat-card label="我的工单" :value="$total" icon="ticket" color="indigo" />
        @endif
    </div>

    @if ($isAgent)
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <a href="{{ ticket_route('index', ['mine' => 1]) }}" class="block">
                <x-stat-card label="指派给我(未完成)" :value="$myOpen" icon="user" color="sky" />
            </a>
            <a href="{{ ticket_route('index', ['unassigned' => 1]) }}" class="block">
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

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 lg:gap-6">
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

    @if ($isAgent)
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6">
            {{-- 我的待处理（按 SLA 优先级排序） --}}
            <x-panel title="我的待处理" icon="ticket">
                @forelse ($myOpenTickets as $t)
                    <a href="{{ ticket_route('show', $t) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/60 transition group">
                        <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400 shrink-0">{{ $t->no }}</span>
                        <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-300">{{ $t->subject }}</span>
                        @if ($t->sla_due_at && ! in_array($t->status, ['resolved', 'closed']))
                            @if ($t->sla_due_at->lt(now()))
                                <span class="shrink-0 inline-flex rounded-md bg-red-50 dark:bg-red-500/10 px-2 py-0.5 text-[11px] font-medium text-red-600 dark:text-red-300 ring-1 ring-inset ring-red-200 dark:ring-red-500/30">超时</span>
                            @elseif ($t->sla_due_at->lt(now()->addHours(6)))
                                <span class="shrink-0 inline-flex rounded-md bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30">临期</span>
                            @endif
                        @endif
                        <x-ticket-priority :priority="$t->priority" class="shrink-0" />
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-gray-400">暂无待处理工单 🎉</p>
                @endforelse
                @if ($myOpenTickets->isNotEmpty())
                    <a href="{{ ticket_route('index', ['mine' => 1]) }}" class="mt-2 inline-block text-xs text-indigo-500 hover:underline">查看全部 {{ $myOpen }} 个 →</a>
                @endif
            </x-panel>

            {{-- 待认领 --}}
            <x-panel title="待认领" icon="ticket">
                @forelse ($unassignedTickets as $t)
                    <a href="{{ ticket_route('show', $t) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/60 transition group">
                        <span class="font-mono text-xs text-gray-400 shrink-0">{{ $t->no }}</span>
                        <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-300">{{ $t->subject }}</span>
                        <span class="shrink-0 text-xs text-gray-400">{{ $t->created_at?->diffForHumans() }}</span>
                        <span class="shrink-0 inline-flex rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 text-[11px] font-medium text-indigo-600 dark:text-indigo-300">认领</span>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-gray-400">没有待认领工单 ✅</p>
                @endforelse
                @if ($unassignedTickets->isNotEmpty())
                    <a href="{{ ticket_route('index', ['unassigned' => 1]) }}" class="mt-2 inline-block text-xs text-indigo-500 hover:underline">查看全部 {{ $unassigned }} 个 →</a>
                @endif
            </x-panel>
        </div>
    @endif

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
            // 相对路径：跟随当前页面 origin，杜绝跨域/混合内容导致的失败
            fetch('/dashboard/recent?scope=' + encodeURIComponent(scope), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                })
                .then(function (r) { if (! r.ok) throw new Error('fail'); return r.text(); })
                .then(function (html) {
                    panel.innerHTML = html;
                    // 替换后强制以点击的 scope 为准（防旧片段/缓存干扰高亮）
                    panel.querySelectorAll('[data-scope]').forEach(function (b) {
                        var active = b.getAttribute('data-scope') === scope;
                        b.className = active
                            ? 'inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-300 shadow-sm font-medium'
                            : 'inline-flex items-center gap-1.5 rounded-md px-3 py-1 transition cursor-pointer text-gray-500 dark:text-gray-400';
                    });
                    history.replaceState(null, '', '?scope=' + scope + '#recent');
                })
                .catch(function () {
                    // 失败时不整页跳转（避免『突然刷新』），高亮保持点击的 scope
                    console.warn('recent panel load failed for scope=' + scope);
                });
        }
        window.__recentPanel = loadRecentPanel;
    </script>

@endsection
