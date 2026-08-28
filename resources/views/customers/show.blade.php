@extends('layouts.app')

@section('page_title', $customer->company ?: '客户 #'.$customer->id)

@section('content')
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.customers.edit', $customer) }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">编辑档案</a>
        <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">返回列表</a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- 档案信息 --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">档案信息</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">公司</dt>
                        <dd class="text-gray-700 dark:text-gray-200 text-right">{{ $customer->company ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">联系人</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $customer->contact_name ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">电话</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $customer->phone ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">邮箱</dt>
                        <dd class="text-gray-700 dark:text-gray-200 break-all text-right">{{ $customer->email ?: '-' }}</dd>
                    </div>
                    @if ($customer->address)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-400 shrink-0">地址</dt>
                            <dd class="text-gray-700 dark:text-gray-200 text-right">{{ $customer->address }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">绑定账号</dt>
                        <dd class="text-gray-700 dark:text-gray-200">
                            @if ($customer->user)
                                {{ $customer->user->name }}（{{ $customer->user->email }}）
                            @else
                                <span class="text-gray-400">未绑定</span>
                            @endif
                        </dd>
                    </div>
                    @if ($customer->remark)
                        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
                            <dt class="text-gray-400 mb-1">备注</dt>
                            <dd class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ $customer->remark }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- 售后状态 --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">产品与售后</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">关联产品</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $customer->product?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">登记时间</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $customer->registered_at?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-400 shrink-0">售后到期</dt>
                        <dd class="text-right">
                            @if ($customer->after_sales_expired_at)
                                @if ($customer->expired)
                                    <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-500/10 dark:text-red-300">{{ $customer->after_sales_expired_at->format('Y-m-d') }} 已过期</span>
                                @elseif ($customer->expiring_soon)
                                    <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300">{{ $customer->after_sales_expired_at->format('Y-m-d') }} 临期</span>
                                @else
                                    <span class="text-gray-700 dark:text-gray-200">{{ $customer->after_sales_expired_at->format('Y-m-d') }}</span>
                                @endif
                            @else
                                <span class="text-gray-400">未设置</span>
                            @endif
                        </dd>
                    </div>
                    @if ($customer->after_sales_expired_at)
                        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                @php
                                    $total = max(1, $customer->registered_at ? $customer->after_sales_expired_at->diffInDays($customer->registered_at) : $customer->product?->warranty_days ?? 1);
                                    $used = $customer->registered_at ? min($total, $customer->registered_at->diffInDays(now())) : 0;
                                @endphp
                                <div class="h-full rounded-full {{ $customer->expired ? 'bg-red-500' : ($customer->expiring_soon ? 'bg-amber-400' : 'bg-green-500') }}"
                                     style="width: {{ round($used / $total * 100) }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">保修期已用 {{ round($used / $total * 100) }}%</p>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- 关联工单 --}}
        <div class="xl:col-span-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">关联工单（{{ $tickets->total() }}）</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                            <th class="py-3 px-4">编号</th>
                            <th class="py-3 px-4">主题</th>
                            <th class="py-3 px-4">分类</th>
                            <th class="py-3 px-4">状态</th>
                            <th class="py-3 px-4">优先级</th>
                            <th class="py-3 px-4">负责人</th>
                            <th class="py-3 px-4">更新时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $t)
                            <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4 font-mono text-xs text-indigo-600 dark:text-indigo-400">
                                    <a href="{{ route('tickets.show', $t) }}">{{ $t->no }}</a>
                                </td>
                                <td class="py-3 px-4 max-w-[220px]">
                                    <a href="{{ route('tickets.show', $t) }}" class="hover:underline line-clamp-1">{{ $t->subject }}</a>
                                </td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $t->category?->name ?? '-' }}</td>
                                <td class="py-3 px-4"><x-ticket-status :status="$t->status" /></td>
                                <td class="py-3 px-4"><x-ticket-priority :priority="$t->priority" /></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $t->assignee?->name ?? '-' }}</td>
                                <td class="py-3 px-4 text-gray-400 whitespace-nowrap">{{ $t->updated_at?->format('m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-12 text-center text-gray-400">该客户暂无工单</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">{{ $tickets->links() }}</div>
        </div>
    </div>
@endsection
