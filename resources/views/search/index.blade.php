@extends('layouts.app')

@section('page_title', '搜索')

@section('content')
    <div class="mb-5">
        <form method="GET" action="{{ route('search') }}" class="flex gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </span>
                <input type="search" name="q" value="{{ $q }}" autofocus
                       placeholder="搜索工单号 / 主题 / 客户 / 产品 / 手机号…"
                       class="w-full rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-900 pl-10 pr-4 py-3 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm">
                搜索
            </button>
        </form>
    </div>

    @if ($q === '')
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 py-16 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            <p class="mt-3 text-sm text-gray-400">输入关键词开始搜索</p>
            <p class="mt-1 text-xs text-gray-400">支持工单编号 / 主题 / 描述、客户公司与联系人、产品名称与 SKU</p>
        </div>
    @else
        @php
            $total = ($tickets?->total() ?? 0) + ($customers?->total() ?? 0) + ($products?->total() ?? 0);
        @endphp

        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            「<span class="font-semibold text-gray-800 dark:text-gray-200">{{ $q }}</span>」共找到
            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $total }}</span> 条结果
        </p>

        @if ($total === 0)
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 py-16 text-center">
                <p class="text-sm text-gray-400">没有找到匹配的内容</p>
                <p class="mt-1 text-xs text-gray-400">试试更短的关键词，或检查是否有错别字</p>
            </div>
        @else
            {{-- 工单 --}}
            @if ($tickets && $tickets->total() > 0)
                <x-panel title="工单（{{ $tickets->total() }}）" icon="ticket" class="mb-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                    <th class="py-2.5 pr-4">编号</th>
                                    <th class="py-2.5 pr-4">主题</th>
                                    <th class="py-2.5 pr-4">状态</th>
                                    <th class="py-2.5 pr-4">优先级</th>
                                    <th class="py-2.5">更新时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tickets as $t)
                                    <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                        <td class="py-3 pr-4 font-mono text-xs text-indigo-600 dark:text-indigo-400">
                                            <a href="{{ route('tickets.show', $t) }}">{{ $t->no }}</a>
                                        </td>
                                        <td class="py-3 pr-4 max-w-[320px]">
                                            <a href="{{ route('tickets.show', $t) }}" class="hover:underline line-clamp-1">{{ $t->subject }}</a>
                                            <span class="text-xs text-gray-400">{{ $t->category?->name ?? '未分类' }}{{ $t->product ? ' · '.$t->product->name : '' }}</span>
                                        </td>
                                        <td class="py-3 pr-4"><x-ticket-status :status="$t->status" /></td>
                                        <td class="py-3 pr-4"><x-ticket-priority :priority="$t->priority" /></td>
                                        <td class="py-3 text-gray-400 whitespace-nowrap">{{ $t->updated_at?->format('m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $tickets->links() }}</div>
                </x-panel>
            @endif

            {{-- 客户 --}}
            @if ($customers && $customers->total() > 0)
                <x-panel title="客户档案（{{ $customers->total() }}）" icon="customer" class="mb-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                    <th class="py-2.5 pr-4">单位名称</th>
                                    <th class="py-2.5 pr-4">联系人</th>
                                    <th class="py-2.5 pr-4">联系电话</th>
                                    <th class="py-2.5">产品</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $c)
                                    <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                        <td class="py-3 pr-4 font-medium text-gray-800 dark:text-gray-200">{{ $c->company }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $c->contact_name ?: '-' }}</td>
                                        <td class="py-3 pr-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $c->phone ?: '-' }}</td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300">{{ $c->product?->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $customers->links() }}</div>
                </x-panel>
            @endif

            {{-- 产品 --}}
            @if ($products && $products->total() > 0)
                <x-panel title="产品（{{ $products->total() }}）" icon="product">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800">
                                    <th class="py-2.5 pr-4">产品名称</th>
                                    <th class="py-2.5 pr-4">SKU</th>
                                    <th class="py-2.5">保修期（天）</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $p)
                                    <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                        <td class="py-3 pr-4 font-medium text-gray-800 dark:text-gray-200">{{ $p->name }}</td>
                                        <td class="py-3 pr-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $p->sku ?: '-' }}</td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300">{{ $p->warranty_days }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $products->links() }}</div>
                </x-panel>
            @endif
        @endif
    @endif
@endsection
