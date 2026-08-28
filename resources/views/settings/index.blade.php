@extends('layouts.app')

@section('page_title', '系统设置')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.settings.save') }}">
            @csrf

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-6">
                {{-- 基本 --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">基本</h3>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">系统名称</label>
                    <input type="text" name="site_name" value="{{ $settings['site_name'] }}" maxlength="50"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                    <p class="mt-1 text-xs text-gray-400">显示在浏览器标题与登录页</p>
                </div>

                {{-- 工单 --}}
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">工单</h3>

                    <label class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="auto_assign" value="1" @checked($settings['auto_assign'] === '1')
                               class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                        新工单自动分配
                        <span class="text-xs text-gray-400">（仅分配给在线客服，按未完成工单数最少优先；关闭后新工单进入待认领）</span>
                    </label>

                    <p class="mt-5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">SLA 时限（小时）</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ([
                            'sla_low' => '低',
                            'sla_normal' => '普通',
                            'sla_high' => '高',
                            'sla_urgent' => '紧急',
                        ] as $key => $label)
                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1">{{ $label }}</label>
                                <input type="number" name="{{ $key }}" value="{{ $settings[$key] }}" min="1" max="720"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 工作时间 --}}
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">工作时间</h3>

                    <label class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="work_hours_enabled" value="1" @checked($settings['work_hours_enabled'] === '1')
                               class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                        启用工作时间限制
                        <span class="text-xs text-gray-400">（非工作时间客户不能提交工单，客服不受限）</span>
                    </label>

                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">开始时间</label>
                            <input type="time" name="work_start" value="{{ $settings['work_start'] }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">结束时间</label>
                            <input type="time" name="work_end" value="{{ $settings['work_end'] }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">工作日</label>
                            <div class="flex flex-wrap gap-3 pt-1.5 text-sm text-gray-700 dark:text-gray-300">
                                @foreach ([1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日'] as $d => $label)
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="work_days[]" value="{{ $d }}"
                                               @checked(in_array((string) $d, explode(',', (string) $settings['work_days']), true))
                                               class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                                        周{{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 运行状态（只读）--}}
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">运行状态</h3>
                    <div class="flex flex-wrap gap-4 text-xs">
                        <span class="inline-flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> 实时服务 ws://127.0.0.1:6001
                        </span>
                        <span class="text-gray-400">后台前缀：/{{ config('app.admin_url') }}</span>
                        <span class="text-gray-400">短信通道：{{ config('services.sms.driver', 'demo') }}</span>
                        <span class="text-gray-400">微信登录：{{ \App\Services\WechatService::enabled() ? '真实模式' : '演示模式' }}</span>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-indigo-500">保存设置</button>
                </div>
            </div>
        </form>
    </div>
@endsection
