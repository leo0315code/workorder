@extends('layouts.app')

@section('page_title', '工单 #'.$ticket->no)

@php $isAgent = auth()->user()->isAgent(); @endphp

@section('content')
    {{-- 页头：面包屑 + 状态徽标 --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <nav class="flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-500 dark:hover:text-indigo-400 transition">仪表盘</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route('tickets.index') }}" class="hover:text-indigo-500 dark:hover:text-indigo-400 transition">工单列表</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            <span class="font-mono text-indigo-600 dark:text-indigo-400 font-medium">{{ $ticket->no }}</span>
        </nav>
        <div class="flex items-center gap-2">
            <x-ticket-status :status="$ticket->status" />
            <x-ticket-priority :priority="$ticket->priority" />
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- 左侧：描述 + 时间线 + 回复 --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- 实时状态指示 --}}
            <div x-data="{ connected: false, fallback: false }"
                 @ticket:status.window="connected = $event.detail.connected; if(!connected) fallback = true"
                 @ticket:fallback.window="fallback = true"
                 class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                <template x-if="connected">
                    <span class="inline-flex items-center gap-1.5 text-green-600 dark:text-green-400">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> 实时连接中
                    </span>
                </template>
                <template x-if="!connected && fallback">
                    <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> 实时服务不可用，已切换为轮询更新
                    </span>
                </template>
            </div>

            {{-- 工单信息卡 --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $ticket->subject }}</h2>
                            <span id="badge-status"><x-ticket-status :status="$ticket->status" /></span>
                            <span id="badge-priority"><x-ticket-priority :priority="$ticket->priority" /></span>
                            @if ($ticket->isOverdue())
                                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30">SLA 已超时</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-400">编号 {{ $ticket->no }} · 创建于 {{ $ticket->created_at?->format('Y-m-d H:i') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-1 text-gray-600 dark:text-gray-300">{{ $ticket->category?->name ?? '未分类' }}</span>
                        <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-1 text-gray-600 dark:text-gray-300">{{ $ticket->product?->name ?? '未关联产品' }}</span>
                    </div>
                </div>

                @if ($ticket->sla_due_at)
                    @php
                        $slaLabel = $ticket->slaLabel();
                        $slaTone = $ticket->isOverdue() ? 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30' : ($ticket->isSlaWarning() ? 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30' : 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30');
                    @endphp
                    <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-800/60 px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                        <span>SLA 时限：{{ $ticket->sla_due_at->format('Y-m-d H:i') }}（{{ \App\Http\Controllers\TicketController::PRIORITY_NAMES[$ticket->priority] }}级）</span>
                        @if ($slaLabel)
                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $slaTone }}">
                                @if ($ticket->isOverdue())
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                @else
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                @endif
                                {{ $slaLabel }}
                            </span>
                        @endif
                    </div>
                @endif

                {{-- 标签（客服可管理；客户只读） --}}
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-gray-400">标签</span>
                        @forelse ($ticket->tags as $tag)
                            <x-tag-badge :tag="$tag" />
                        @empty
                            <span class="text-xs text-gray-300 dark:text-gray-600">暂无</span>
                        @endforelse

                        @if ($isAgent)
                            <details class="relative inline-block">
                                <summary class="inline-flex items-center gap-1 rounded-md border border-dashed border-gray-300 dark:border-gray-600 px-2 py-0.5 text-xs text-gray-400 hover:text-indigo-500 hover:border-indigo-300 cursor-pointer list-none">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    添加
                                </summary>
                                <form method="POST" action="{{ route('tickets.tags', $ticket) }}"
                                      class="absolute right-0 top-full mt-2 z-30 w-56 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-3">
                                    @csrf
                                    <div class="space-y-1.5 max-h-40 overflow-y-auto">
                                        @foreach ($allTags as $t)
                                            <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md px-2 py-1">
                                                <input type="checkbox" name="tag_ids[]" value="{{ $t->id }}"
                                                       @checked($ticket->tags->contains('id', $t->id))
                                                       class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <input type="text" name="tags[]" placeholder="输入新标签名后保存" maxlength="30"
                                           class="mt-2 w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500">
                                    <button type="submit" class="mt-2 w-full rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 transition">保存标签</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </div>

                <div class="mt-4 prose prose-sm max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $ticket->description }}</div>

                @if ($ticket->attachments->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <p class="text-xs font-medium text-gray-400 mb-2">附件（{{ $ticket->attachments->count() }}）</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ticket->attachments as $att)
                                <a href="{{ route('attachments.download', $att) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs text-gray-600 dark:text-gray-300 hover:border-indigo-300 hover:text-indigo-600">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                                    <span class="max-w-[180px] truncate">{{ $att->original_name }}</span>
                                    <span class="text-gray-400">({{ number_format($att->size / 1024, 1) }}KB)</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- 满意度评分 --}}
            @php $rating = $ticket->rating; @endphp
            @if (! $isAgent && in_array($ticket->status, ['resolved', 'closed']) && ! $rating)
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-6" x-data="{ stars: 0, comment: '' }">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">本次服务体验如何？</h3>
                    <form method="POST" action="{{ route('tickets.rate', $ticket) }}">
                        @csrf
                        <div class="flex gap-1 mb-3">
                            <template x-for="i in 5" :key="i">
                                <button type="button" @click="stars = i"
                                        class="text-2xl transition" :class="i <= stars ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600'">★</button>
                            </template>
                        </div>
                        <input type="hidden" name="rating" :value="stars" x-bind:required="stars > 0">

                        <div class="mb-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">问题是否已解决？<span class="text-red-500">*</span></p>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 cursor-pointer has-[:checked]:bg-green-50 has-[:checked]:border-green-300 has-[:checked]:text-green-700 dark:has-[:checked]:bg-green-500/10 dark:has-[:checked]:text-green-300 transition">
                                    <input type="radio" name="is_solved" value="1" required class="accent-green-600">
                                    <span class="text-sm">✅ 已解决</span>
                                </label>
                                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 cursor-pointer has-[:checked]:bg-red-50 has-[:checked]:border-red-300 has-[:checked]:text-red-700 dark:has-[:checked]:bg-red-500/10 dark:has-[:checked]:text-red-300 transition">
                                    <input type="radio" name="is_solved" value="0" required class="accent-red-600">
                                    <span class="text-sm">❌ 未解决</span>
                                </label>
                            </div>
                        </div>

                        <textarea name="comment" x-model="comment" rows="2" maxlength="500" placeholder="想说的话（可选）…"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm"></textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" :disabled="stars === 0"
                                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">提交评价</button>
                        </div>
                    </form>
                </div>
            @elseif ($rating)
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">满意度评价</h3>
                    <div class="flex items-center gap-2">
                        <div class="flex gap-0.5">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="text-lg {{ $i <= $rating->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">★</span>
                            @endfor
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $rating->rating }} 分</span>
                        @if ($rating->rating >= 4)
                            <span class="inline-flex rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200 dark:bg-green-500/10 dark:text-green-300">满意</span>
                        @elseif ($rating->rating === 3)
                            <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300">一般</span>
                        @else
                            <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-500/10 dark:text-red-300">不满意</span>
                        @endif
                        @if (! is_null($rating->is_solved))
                            <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $rating->is_solved ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30' : 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30' }}">
                                {{ $rating->is_solved ? '问题已解决' : '问题未解决' }}
                            </span>
                        @endif
                    </div>
                    @if ($rating->comment)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">“{{ $rating->comment }}”</p>
                    @endif
                    @if ($rating->created_at)
                        <p class="mt-1 text-xs text-gray-400">评价于 {{ $rating->created_at->format('Y-m-d H:i') }}</p>
                    @endif
                </div>
            @endif

            {{-- 时间线 --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">沟通记录</h3>
                <div id="timeline" class="space-y-4" x-data="ticketRoom({{ json_encode($roomConfig) }})">
                    @foreach ($ticket->replies as $reply)
                        @include('tickets.partials.reply', ['reply' => $reply])
                    @endforeach
                </div>
            </div>

            {{-- 回复表单 --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">
                    {{ $isAgent ? '回复客户' : '补充说明 / 回复' }}
                </h3>
                <form method="POST" action="{{ route('tickets.reply', $ticket) }}"
                      enctype="multipart/form-data"
                      x-data="{ content: '', quick: '', files: [] }"
                      @submit="if (content.trim() === '') { $event.preventDefault(); }">
                    @csrf
                    @if ($isAgent && $quickReplies->isNotEmpty())
                        <div class="mb-3">
                            <select x-model="quick" @change="if (quick) { content = (content ? content + '\n\n' : '') + quick; quick = ''; }"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                <option value="">插入快捷回复模板…</option>
                                @foreach ($quickReplies as $q)
                                    <option value="{{ $q->content }}">{{ $q->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <textarea name="content" x-model="content" required rows="4" maxlength="10000" placeholder="输入回复内容…"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    @error('content')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    <div class="mt-3">
                        <label class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-indigo-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                            附带附件（最多 5 个，每个 ≤10MB）
                            <input type="file" name="attachments[]" multiple class="hidden"
                                   @change="files = Array.from($event.target.files).map(f => f.name)">
                        </label>
                        <div class="mt-1.5 space-y-0.5">
                            <template x-for="f in files" :key="f">
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="f"></p>
                            </template>
                        </div>
                        @error('attachments')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500">发送回复</button>
                    </div>
                </form>
            </div>

            {{-- 内部备注（客服）--}}
            @if ($isAgent)
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-500/5 p-6">
                    <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-300 mb-4">内部备注（客户不可见）</h3>
                    <form method="POST" action="{{ route('tickets.note', $ticket) }}">
                        @csrf
                        <textarea name="content" rows="3" maxlength="10000" placeholder="仅客服可见的备注…"
                                  class="w-full rounded-lg border-amber-300 dark:border-amber-500/40 dark:bg-gray-900 text-sm shadow-sm focus:ring-amber-500 focus:border-amber-500"></textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-medium text-white hover:bg-amber-500">添加备注</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 操作记录（客服）--}}
            @if ($isAgent && $ticket->logs->isNotEmpty())
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">操作记录</h3>
                    <ol class="space-y-3">
                        @foreach ($ticket->logs as $log)
                            <li class="flex gap-3 text-sm">
                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600 shrink-0"></span>
                                <div class="min-w-0">
                                    <p class="text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">{{ $log->user?->name ?? '系统' }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">{{ \App\Models\TicketLog::DESCRIPTIONS[$log->action] ?? $log->action }}</span>
                                        @if ($log->field === 'status' && $log->new_value)
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $log->new_value }}</span>
                                        @elseif ($log->field === 'priority' && $log->new_value)
                                            <span class="text-orange-600 dark:text-orange-400 font-medium">{{ $log->new_value }}</span>
                                        @elseif ($log->field === 'assignee')
                                            <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $log->new_value }}</span>
                                        @endif
                                        @if ($log->note)
                                            <span class="text-gray-400">（{{ $log->note }}）</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at?->format('Y-m-d H:i:s') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        {{-- 右侧：信息 + 客服操作 --}}
        <div class="space-y-6">
            {{-- 客户信息 --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">客户信息</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">提交人</dt>
                        <dd class="text-gray-700 dark:text-gray-200 text-right">{{ $ticket->user?->name }}</dd>
                    </div>
                    @if ($ticket->user?->phone)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-400 shrink-0">电话</dt>
                            <dd class="text-gray-700 dark:text-gray-200">{{ $ticket->user->phone }}</dd>
                        </div>
                    @endif
                    @if ($ticket->user?->email)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-400 shrink-0">邮箱</dt>
                            <dd class="text-gray-700 dark:text-gray-200 break-all text-right">{{ $ticket->user->email }}</dd>
                        </div>
                    @endif
                    @if ($ticket->customer)
                        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-800 space-y-3">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400 shrink-0">客户档案</dt>
                                <dd class="text-gray-700 dark:text-gray-200 text-right">{{ $ticket->customer->company ?: $ticket->customer->contact_name ?: '#' . $ticket->customer->id }}</dd>
                            </div>
                            @if ($ticket->customer->after_sales_expired_at)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-400 shrink-0">售后到期</dt>
                                    <dd class="text-right {{ $ticket->customer->expired ? 'text-red-500 font-medium' : 'text-gray-700 dark:text-gray-200' }}">
                                        {{ $ticket->customer->after_sales_expired_at->format('Y-m-d') }}
                                        {{ $ticket->customer->expired ? '（已过期）' : '' }}
                                    </dd>
                                </div>
                            @endif
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">负责人</dt>
                        @if ($ticket->assignee)
                            <dd class="text-gray-700 dark:text-gray-200">{{ $ticket->assignee->name }}</dd>
                        @else
                            <dd class="text-right">
                                <span class="inline-flex rounded-md bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30">待认领 · 客服上线后接单</span>
                            </dd>
                        @endif
                    </div>
                </dl>
            </div>

            {{-- 客服操作面板 --}}
            @if ($isAgent)
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">处理操作</h3>

                    @if (! $ticket->assignee_id)
                        <form method="POST" action="{{ route('tickets.claim', $ticket) }}" class="mb-4">
                            @csrf
                            <button type="submit" class="w-full rounded-lg bg-green-600 py-2.5 text-sm font-medium text-white hover:bg-green-500 shadow-sm">
                                认领此工单（指派给自己）
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">状态</label>
                            <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                @foreach (\App\Http\Controllers\TicketController::STATUS_NAMES as $k => $label)
                                    <option value="{{ $k }}" @selected($ticket->status === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">优先级</label>
                            <select name="priority" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                @foreach (\App\Http\Controllers\TicketController::PRIORITY_NAMES as $k => $label)
                                    <option value="{{ $k }}" @selected($ticket->priority === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">指派给</label>
                            <select name="assignee_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                <option value="">未指派</option>
                                @foreach ($agents as $a)
                                    <option value="{{ $a->id }}" @selected($ticket->assignee_id === $a->id)>{{ $a->name }}{{ in_array($a->id, $onlineAgentIds, true) ? ' · 在线' : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-gray-900 dark:bg-gray-100 py-2 text-sm font-medium text-white dark:text-gray-900 hover:bg-gray-700">保存更改</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <script>
        function ticketRoom(config) {
            return {
                lastReplyId: config.lastReplyId,
                realtime: null,
                init() {
                    try {
                        this.realtime = new TicketRealtime(config);
                    } catch (e) {
                        this.startPolling();
                    }
                    window.addEventListener('ticket:event', (e) => this.onEvent(e.detail));
                    window.addEventListener('ticket:fallback', () => this.startPolling());
                },
                onEvent(msg) {
                    if (msg.type === 'reply') this.appendReply(msg.reply);
                    if (msg.type === 'status_changed') this.applyStatus(msg.ticket);
                },
                appendReply(reply) {
                    if (reply.id <= this.lastReplyId) return;
                    this.lastReplyId = reply.id;
                    const el = document.createElement('div');
                    el.innerHTML = this.renderReply(reply);
                    document.getElementById('timeline').appendChild(el);
                },
                applyStatus(ticket) {
                    const s = document.getElementById('badge-status');
                    if (s) {
                        s.innerHTML = this.statusBadge(ticket.status, ticket.status_label);
                        s.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                },
                startPolling() {
                    if (this.pollTimer) return;
                    this.pollTimer = setInterval(() => this.poll(), 8000);
                },
                poll() {
                    fetch(config.pollUrl + '?after=' + this.lastReplyId)
                        .then((r) => r.json())
                        .then((data) => {
                            (data.replies || []).forEach((reply) => this.appendReply(reply));
                        })
                        .catch(() => {});
                },
                statusBadge(status, label) {
                    const map = {
                        open: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
                        pending: 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-500/30',
                        in_progress: 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30',
                        resolved: 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-500/10 dark:text-green-300 dark:ring-green-500/30',
                        closed: 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700',
                    };
                    return '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset ' + (map[status] || map.closed) + '">' + (label || status) + '</span>';
                },
                renderReply(reply) {
                    const isAgentSide = reply.user && ['agent', 'admin'].includes(reply.user.role);
                    const avatar = (reply.user?.name || '?').charAt(0).toUpperCase();
                    const name = reply.user?.name || '用户';
                    const time = reply.created_at || '';
                    return `<div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 ${isAgentSide ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'}">${avatar}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">${name}</span>
                                ${isAgentSide ? '<span class="text-xs text-indigo-500">客服</span>' : ''}
                                <span class="text-xs text-gray-400">${time}</span>
                            </div>
                            <div class="mt-1 rounded-lg bg-gray-50 dark:bg-gray-800/70 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">${reply.content}</div>
                        </div>
                    </div>`;
                },
            };
        }
    </script>
@endsection
