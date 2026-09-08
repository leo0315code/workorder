@extends('layouts.app')

@section('page_title', '登录审计')

@section('content')
    <div class="space-y-4">
        {{-- 页头 --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-1 h-6 rounded-full bg-gradient-to-b from-indigo-500 to-violet-500 inline-block"></span>
                    登录审计
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">记录所有登录 / 尝试登录（成功与失败），用于安全排查</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    今日成功 {{ $todayCount - $failedToday }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 dark:bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-700 dark:text-rose-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    今日失败 {{ $failedToday }}
                </span>
            </div>
        </div>

        {{-- 筛选 --}}
        <form method="GET" action="{{ route('admin.login-audits.index') }}" class="flex flex-wrap items-center gap-2.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 shadow-sm">
            <input type="text" name="account" value="{{ request('account') }}" placeholder="账号（邮箱/手机号）"
                   class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 w-56">
            <select name="channel" class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 px-3 py-2">
                <option value="">全部通道</option>
                @foreach (['web' => '用户端', 'admin' => '管理端', 'phone' => '手机号', 'wechat' => '微信', 'api' => 'API'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('channel') === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="result" class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 px-3 py-2">
                <option value="">全部结果</option>
                <option value="success" @selected(request('result') === 'success')>成功</option>
                <option value="failed" @selected(request('result') === 'failed')>失败</option>
            </select>
            <button class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:from-indigo-600 hover:to-violet-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                筛选
            </button>
            @if (request()->anyFilled(['account', 'channel', 'result']))
                <a href="{{ route('admin.login-audits.index') }}" class="text-sm text-gray-500 hover:text-indigo-500 transition">清除筛选</a>
            @endif
        </form>

        {{-- 列表 --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">时间</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">账号</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">用户</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">通道</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">IP</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">结果</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">原因</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($audits as $audit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">{{ $audit->login_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-3 max-w-[180px] truncate text-gray-800 dark:text-gray-100">{{ $audit->email }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $audit->user?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $channelMap = ['web' => ['用户端', 'sky'], 'admin' => ['管理端', 'violet'], 'phone' => ['手机号', 'emerald'], 'wechat' => ['微信', 'amber'], 'api' => ['API', 'cyan']];
                                        [$cn, $color] = $channelMap[$audit->channel] ?? [$audit->channel, 'gray'];
                                    @endphp
                                    <span class="inline-flex rounded-full bg-{{ $color }}-50 dark:bg-{{ $color }}-500/10 px-2.5 py-0.5 text-xs font-medium text-{{ $color }}-700 dark:text-{{ $color }}-300">{{ $cn }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-gray-500 dark:text-gray-400">{{ $audit->ip }}</td>
                                <td class="px-4 py-3">
                                    @if ($audit->success)
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs font-medium">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                            成功
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 text-xs font-medium">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                            失败
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500">
                                    @if (! $audit->success)
                                        @php
                                            $reasons = ['bad_credentials' => '账号或密码错误', 'not_agent' => '非客服角色', 'code_invalid' => '验证码错误', 'rate_limited' => '触发限流'];
                                        @endphp
                                        <span class="text-rose-500 dark:text-rose-400">{{ $reasons[$audit->reason] ?? $audit->reason }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                                        <span>暂无登录记录</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                {{ $audits->links() }}
            </div>
        </div>
    </div>
@endsection
