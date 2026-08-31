@extends('layouts.app')

@section('page_title', '工单列表')

@section('content')
    @php $isAgent = auth()->user()->isAgent(); @endphp

    <div x-data="ticketList({{ json_encode($wsConfig) }})">
        {{-- 筛选（移动端折叠：默认收起，已选筛选项时自动展开） --}}
        <form method="GET" action="{{ route('tickets.index') }}" x-data="{ open: {{ $activeFilterCount > 0 ? 'true' : 'false' }}, count: {{ (int) $activeFilterCount }} }" class="mb-5">
            {{-- 移动端折叠触发 --}}
            <div class="md:hidden mb-3 flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 shadow-sm">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 7.994 3Z" /></svg>
                    筛选
                    <span x-show="count > 0" class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-indigo-600 text-white text-[11px] font-semibold" x-text="count"></span>
                </button>
                @if ($activeFilterCount > 0)
                    <a href="{{ route('tickets.index') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">重置</a>
                @endif
            </div>

            {{-- 筛选区：移动端折叠，桌面始终显示 --}}
            <div :class="open ? 'block' : 'hidden md:block'">
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                <div class="relative">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="搜索编号 / 主题 / 描述"
                           class="w-64 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    <option value="">全部状态</option>
                    @foreach ($statuses as $k => $label)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="priority" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    <option value="">全部优先级</option>
                    @foreach ($priorities as $k => $label)
                        <option value="{{ $k }}" @selected(request('priority') === $k)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="category" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    <option value="">全部分类</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((string) request('category') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                @if ($isAgent)
                    <a href="{{ route('tickets.index', array_merge(request()->query(), ['unassigned' => 1])) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition
                              {{ request()->boolean('unassigned') ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20' }}">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> 待认领
                    </a>
                    <a href="{{ route('tickets.index', array_merge(request()->query(), ['overdue' => 1])) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition
                              {{ request()->boolean('overdue') ? 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-300' : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/20' }}">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> SLA 超时
                    </a>
                    <select name="assignee" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        <option value="">全部负责人</option>
                        @foreach ($agents as $a)
                            <option value="{{ $a->id }}" @selected((string) request('assignee') === (string) $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="mine" value="1" @checked(request()->boolean('mine')) class="rounded border-gray-300 dark:border-gray-700">
                        只看指派给我
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="unassigned" value="1" @checked(request()->boolean('unassigned')) class="rounded border-gray-300 dark:border-gray-700">
                        未指派
                    </label>
                @endif

                <button type="submit" class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-700">筛选</button>
                <a href="{{ route('tickets.index') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">重置</a>
            </div>
            </div>
        </form>

        {{-- 工具条 --}}
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <a href="{{ route('tickets.create') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-500/35 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                新建工单
            </a>
            @if ($isAgent)
                <a href="{{ route('tickets.export', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    导出 CSV（当前筛选）
                </a>
            @endif

            <template x-if="isAgent && selected.length > 0">
                <form method="POST" action="{{ route('tickets.batch') }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50 dark:bg-indigo-500/10 px-3 py-2">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ticket_ids[]" :value="id">
                    </template>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-500/30" x-text="'已选 ' + selected.length + ' 个'"></span>
                    <select name="action" class="rounded-lg border-indigo-300 dark:border-indigo-500/40 dark:bg-gray-900 text-sm" @change="batchAction = $event.target.value">
                        <option value="close">批量关闭</option>
                        <option value="assign">批量指派</option>
                    </select>
                    <select name="assignee_id" x-show="batchAction === 'assign'" class="rounded-lg border-indigo-300 dark:border-indigo-500/40 dark:bg-gray-900 text-sm">
                        <option value="">选择客服…</option>
                        @foreach ($agents as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}{{ in_array($a->id, $onlineAgentIds, true) ? ' · 在线' : '' }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-500/35 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" onclick="return confirm('确认执行批量操作？');">执行</button>
                    <button type="button" @click="selected = []" class="text-sm text-indigo-500 hover:underline">取消</button>
                </form>
            </template>
        </div>

        {{-- 实时更新提示 --}}
        <div @ticket:event.window="onLiveEvent($event.detail)" class="mb-4">
            <div x-show="dirty" x-transition
                 class="flex items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 dark:border-indigo-500/30 dark:bg-indigo-500/10 px-4 py-3 text-sm text-indigo-700 dark:text-indigo-300">
                <span>有新工单/更新，点击刷新查看</span>
                <button @click="location.reload()" class="font-medium underline">刷新</button>
            </div>
        </div>

        {{-- 移动端卡片列表 --}}
        @if ($tickets->isEmpty())
            <div class="md:hidden rounded-xl border border-dashed border-gray-300 dark:border-gray-700 py-14 text-center">
                <p class="text-sm text-gray-400">暂无工单</p>
                @if (! $isAgent)
                    <a href="{{ route('tickets.create') }}" class="mt-2 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">去创建第一个工单 →</a>
                @endif
            </div>
        @endif
        @if ($tickets->isNotEmpty())
            <div class="md:hidden space-y-3 mb-4">
                @foreach ($tickets as $t)
                    <a href="{{ route('tickets.show', $t) }}" class="block rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm active:scale-[0.99] transition">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs text-indigo-600 dark:text-indigo-400">{{ $t->no }}</span>
                            <div class="flex items-center gap-1.5">
                                <x-ticket-priority :priority="$t->priority" />
                                @if ($t->isOverdue() && $isAgent)
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
                            <span>{{ $t->category?->name ?? '未分类' }}</span>
                            <span>·</span>
                            <span>{{ $t->product?->name ?? '无产品' }}</span>
                            @if ($isAgent && $t->assignee)
                                <span class="ml-auto">{{ $t->assignee->name }}</span>
                            @elseif ($isAgent)
                                <span class="ml-auto text-amber-600 dark:text-amber-400">待认领</span>
                            @endif
                        </div>
                    </a>
                @endforeach
                <div class="pt-1">
                    {{ $tickets->links() }}
                </div>
            </div>
        @endif

        {{-- 桌面端表格 --}}
        <div class="hidden md:block rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                            @if ($isAgent)
                                <th class="py-3 pl-4 pr-2 w-10">
                                    <input type="checkbox" @change="toggleAll($event.target.checked)" :checked="allSelected"
                                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                </th>
                            @endif
                            <th class="py-3 px-4">编号</th>
                            <th class="py-3 px-4">主题</th>
                            @if ($isAgent)<th class="py-3 px-4">客户</th>@endif
                            <th class="py-3 px-4">分类</th>
                            <th class="py-3 px-4">产品</th>
                            <th class="py-3 px-4">状态</th>
                            <th class="py-3 px-4">优先级</th>
                            @if ($isAgent)<th class="py-3 px-4">负责人</th>@endif
                            @if ($isAgent)<th class="py-3 px-4">SLA</th>@endif
                            <th class="py-3 px-4">更新时间</th>
                            <th class="py-3 px-4 text-right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $t)
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-indigo-50/40 dark:hover:bg-indigo-500/5 transition">
                                @if ($isAgent)
                                    <td class="py-3 pl-4 pr-2">
                                        <input type="checkbox" value="{{ $t->id }}" x-model="selected"
                                               class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                @endif
                                <td class="py-3 px-4 font-mono text-xs text-indigo-600 dark:text-indigo-400">{{ $t->no }}</td>
                                <td class="py-3 px-4 max-w-[240px]">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('tickets.show', $t) }}" class="font-medium text-gray-800 dark:text-gray-200 hover:underline line-clamp-1">{{ $t->subject }}</a>
                                        @if ($t->isOverdue())
                                            <span class="shrink-0 inline-flex rounded-md bg-red-50 dark:bg-red-500/10 px-1.5 py-0.5 text-[10px] font-medium text-red-600 dark:text-red-300 ring-1 ring-inset ring-red-200 dark:ring-red-500/30">超时</span>
                                        @endif
                                    </div>
                                </td>
                                @if ($isAgent)
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $t->user?->name ?? '-' }}</td>
                                @endif
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $t->category?->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $t->product?->name ?? '-' }}</td>
                                <td class="py-3 px-4"><x-ticket-status :status="$t->status" /></td>
                                <td class="py-3 px-4"><x-ticket-priority :priority="$t->priority" /></td>
                                @if ($isAgent)
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                        @if ($t->assignee)
                                            {{ $t->assignee->name }}
                                        @else
                                            <span class="text-red-500">未指派</span>
                                        @endif
                                    </td>
                                @endif
                                @if ($isAgent)
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @php $sla = $t->slaLabel(); @endphp
                                        @if ($sla)
                                            <span class="inline-flex rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset
                                                {{ $t->isOverdue() ? 'bg-red-50 text-red-600 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30' : ($t->isSlaWarning() ? 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30' : 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30') }}">
                                                {{ $sla }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="py-3 px-4 text-gray-400 whitespace-nowrap">{{ $t->updated_at?->format('m-d H:i') }}</td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $t) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">查看</a>
                                    @if ($isAgent && ! $t->assignee)
                                        <form method="POST" action="{{ route('tickets.claim', $t) }}" class="inline ml-2">
                                            @csrf
                                            <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">认领</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-gray-400">暂无工单</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>

    <script>
        function ticketList(config) {
            return {
                isAgent: @json($isAgent),
                selected: [],
                batchAction: 'close',
                dirty: false,
                wsConnected: false,
                realtime: null,
                pollTimer: null,

                init() {
                    // 建立实时连接（ticket.all 房间），新工单/更新即时提示
                    try {
                        this.realtime = new TicketRealtime(config);
                    } catch (e) { /* noop */ }
                    // WS 4 秒未连接成功 → 启动轮询兜底
                    setTimeout(() => {
                        if (!this.wsConnected) this.startPolling();
                    }, 4000);
                    window.addEventListener('ticket:status', (e) => {
                        this.wsConnected = !!e.detail.connected;
                        if (!this.wsConnected) this.startPolling();
                    });
                    window.addEventListener('ticket:fallback', () => this.startPolling());
                },

                startPolling() {
                    if (this.pollTimer) return;
                    this.pollTimer = setInterval(() => this.poll(), 20000);
                },

                poll() {
                    fetch(config.pollUrl + '?since=' + encodeURIComponent(config.lastUpdated))
                        .then((r) => r.json())
                        .then((d) => { if (d.count > 0) this.dirty = true; })
                        .catch(() => {});
                },

                get allSelected() {
                    const ids = @json($tickets->pluck('id')->all());
                    return ids.length > 0 && ids.every((id) => this.selected.includes(id));
                },
                toggleAll(checked) {
                    const ids = @json($tickets->pluck('id')->all());
                    this.selected = checked ? [...new Set([...this.selected, ...ids])] : this.selected.filter((id) => !ids.includes(id));
                },
                onLiveEvent(msg) {
                    if (['new_ticket', 'status_changed', 'reply', 'notification'].includes(msg.type)) {
                        this.dirty = true;
                    }
                },
            };
        }
    </script>
@endsection
