@extends('layouts.app')

@section('page_title', '客户档案')

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.customers.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            新建客户
        </a>
        <a href="{{ route('admin.customers.export', request()->query()) }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            导出 CSV
        </a>
        <label class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
            导入 CSV
            <input type="file" accept=".csv" class="hidden" x-data
                   @change="if ($event.target.files.length) { const f = $event.target.files[0]; const fd = new FormData(); fd.append('file', f); fd.append('_token', document.querySelector('meta[name=csrf-token]').content); fetch('{{ route('admin.customers.import') }}', { method: 'POST', body: fd }).then(() => location.reload()); }">
        </label>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            售后已过期 <span class="font-semibold text-red-500">{{ $expiredCount }}</span> 家 · 7 天内到期 <span class="font-semibold text-amber-500">{{ $expiringCount }}</span> 家
        </span>
    </div>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="mb-4 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="搜索公司 / 联系人 / 电话 / 邮箱"
               class="w-72 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
        <select name="warranty" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
            <option value="">全部售后状态</option>
            <option value="active" @selected(request('warranty') === 'active')>在保</option>
            <option value="expiring" @selected(request('warranty') === 'expiring')>临期（7天）</option>
            <option value="expired" @selected(request('warranty') === 'expired')>已过期</option>
        </select>
        <button type="submit" class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-sm font-medium text-white dark:text-gray-900">筛选</button>
    </form>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-400 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60">
                        <th class="py-3 px-4">公司 / 联系人</th>
                        <th class="py-3 px-4">联系方式</th>
                        <th class="py-3 px-4">关联产品</th>
                        <th class="py-3 px-4">登记时间</th>
                        <th class="py-3 px-4">售后到期</th>
                        <th class="py-3 px-4">绑定账号</th>
                        <th class="py-3 px-4 text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $c)
                        <tr class="border-b border-gray-100 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $c->company ?: '（未填公司）' }}</p>
                                <p class="text-xs text-gray-400">{{ $c->contact_name ?: '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-gray-600 dark:text-gray-300">{{ $c->phone ?: '-' }}</p>
                                <p class="text-xs text-gray-400">{{ $c->email ?: '' }}</p>
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $c->product?->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $c->registered_at?->format('Y-m-d') ?? '-' }}</td>
                            <td class="py-3 px-4">
                                @if ($c->after_sales_expired_at)
                                    @if ($c->expired)
                                        <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-500/10 dark:text-red-300">{{ $c->after_sales_expired_at->format('Y-m-d') }} 已过期</span>
                                    @elseif ($c->expiring_soon)
                                        <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300">{{ $c->after_sales_expired_at->format('Y-m-d') }} 临期</span>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-300">{{ $c->after_sales_expired_at->format('Y-m-d') }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                @if ($c->user)
                                    {{ $c->user->name }}
                                @else
                                    <span class="text-gray-400">未绑定</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.customers.show', $c) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">详情</a>
                                <a href="{{ route('admin.customers.edit', $c) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3">编辑</a>
                                <form method="POST" action="{{ route('admin.customers.destroy', $c) }}" class="inline"
                                      onsubmit="return confirm('确定删除该客户档案？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">删除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">暂无客户档案</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $customers->links() }}
        </div>
    </div>
@endsection
