@extends('layouts.app')

@section('page_title', '系统设置')

@section('content')
    <div class="max-w-5xl space-y-4">
        {{-- 角色管理快捷入口 --}}
        <a href="{{ route('admin.agent-roles.index') }}"
           class="flex items-center justify-between rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-gradient-to-r from-indigo-50 to-violet-50 dark:from-indigo-500/10 dark:to-violet-500/10 px-5 py-3.5 text-sm hover:from-indigo-100 hover:to-violet-100 dark:hover:from-indigo-500/20 dark:hover:to-violet-500/20 shadow-sm transition">
            <span class="flex items-center gap-2.5 text-indigo-700 dark:text-indigo-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                <strong class="font-semibold">客服角色管理</strong>
                <span class="text-xs text-indigo-500/80 dark:text-indigo-300/80">维护客服角色模板（主管/接单员/档案等），并分配给具体客服</span>
            </span>
            <span class="text-indigo-600 dark:text-indigo-300 font-medium whitespace-nowrap">前往管理 →</span>
        </a>

        <form method="POST" action="{{ route('admin.settings.save') }}" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                {{-- 基本 --}}
                <x-settings-section title="基本" icon="globe">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">系统名称 <span class="text-red-500">*</span></label>
                        <input type="text" name="site_name" value="{{ $settings['site_name'] }}" required maxlength="50"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1.5 text-xs text-gray-400">显示在浏览器标题与登录页</p>
                    </div>
                </x-settings-section>

                {{-- 通知 --}}
                <x-settings-section title="通知" icon="bell">
                    <label class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-500/40 cursor-pointer">
                        <input type="checkbox" name="email_notify_enabled" value="1" @checked($settings['email_notify_enabled'] === '1')
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">新工单 / 新回复 / 状态变更发送邮件提醒</span>
                            <span class="block text-xs text-gray-400 mt-0.5">发送渠道由 .env 的 MAIL_MAILER 决定，log 为写日志，smtp 为真实发送</span>
                        </span>
                    </label>
                </x-settings-section>

                {{-- 工单 --}}
                <x-settings-section title="工单" icon="ticket" divider>
                    <label class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-500/40 cursor-pointer">
                        <input type="checkbox" name="auto_assign" value="1" @checked($settings['auto_assign'] === '1')
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">新工单自动分配</span>
                            <span class="block text-xs text-gray-400 mt-0.5">仅分配给在线客服，按未完成工单数最少优先；关闭后新工单进入待认领</span>
                        </span>
                    </label>

                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">SLA 时限（小时）</p>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach (['sla_low' => '低', 'sla_normal' => '普通', 'sla_high' => '高', 'sla_urgent' => '紧急'] as $key => $label)
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
                                    <input type="number" name="{{ $key }}" value="{{ $settings[$key] }}" min="1" max="720"
                                           class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-settings-section>

                {{-- 工作时间 --}}
                <x-settings-section title="工作时间" icon="clock" divider>
                    <label class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-500/40 cursor-pointer">
                        <input type="checkbox" name="work_hours_enabled" value="1" @checked($settings['work_hours_enabled'] === '1')
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">启用工作时间限制</span>
                            <span class="block text-xs text-gray-400 mt-0.5">非工作时间客户不能提交工单，客服不受限</span>
                        </span>
                    </label>

                    <div class="grid grid-cols-[1fr_1fr_auto] gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">开始</label>
                            <input type="time" name="work_start" value="{{ $settings['work_start'] }}"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">结束</label>
                            <input type="time" name="work_end" value="{{ $settings['work_end'] }}"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">工作日</label>
                            <div class="flex gap-1">
                                @foreach ([1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日'] as $d => $label)
                                    <label class="flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-500/40 text-xs text-gray-600 dark:text-gray-300 cursor-pointer has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-300 has-[:checked]:text-indigo-700 dark:has-[:checked]:bg-indigo-500/20 dark:has-[:checked]:text-indigo-300 transition">
                                        <input type="checkbox" name="work_days[]" value="{{ $d }}" @checked(in_array((string) $d, explode(',', (string) $settings['work_days']), true))
                                               class="hidden">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-settings-section>
            </div>

            {{-- 短信通道（横跨整行） --}}
            <x-settings-section title="短信通道" icon="chat" divider>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">发送驱动</label>
                        <select name="sms_driver" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="demo" @selected($settings['sms_driver'] === 'demo')>demo（本地联调，验证码直显）</option>
                            <option value="aliyun" @selected($settings['sms_driver'] === 'aliyun')>阿里云短信</option>
                            <option value="tencent" @selected($settings['sms_driver'] === 'tencent')>腾讯云短信</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-800 cursor-pointer">
                            <input type="checkbox" name="sms_allow_demo_code" value="1" @checked($settings['sms_allow_demo_code'] === '1')
                                   class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">允许万能验证码 <code class="font-mono text-xs px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800">123456</code></span>
                            <span class="text-xs text-gray-400">（仅测试用）</span>
                        </label>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-orange-500"></span> 阿里云（AccessKey / 签名 / 模板）
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" name="sms_aliyun_access_key_id" value="{{ $settings['sms_aliyun_access_key_id'] }}" placeholder="AccessKey ID（留空不修改）"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="password" name="sms_aliyun_access_key_secret" value="{{ $settings['sms_aliyun_access_key_secret'] }}" placeholder="AccessKey Secret（留空不修改）"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="text" name="sms_aliyun_sign_name" value="{{ $settings['sms_aliyun_sign_name'] }}" placeholder="短信签名"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="text" name="sms_aliyun_template_code" value="{{ $settings['sms_aliyun_template_code'] }}" placeholder="模板编码（如 SMS_123456）"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1.5">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500"></span> 腾讯云（SecretId/SecretKey/SDKAppID/签名/模板）
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" name="sms_tencent_secret_id" value="{{ $settings['sms_tencent_secret_id'] }}" placeholder="SecretId（留空不修改）"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="password" name="sms_tencent_secret_key" value="{{ $settings['sms_tencent_secret_key'] }}" placeholder="SecretKey（留空不修改）"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="text" name="sms_tencent_sdk_app_id" value="{{ $settings['sms_tencent_sdk_app_id'] }}" placeholder="SDKAppID"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="text" name="sms_tencent_sign_name" value="{{ $settings['sms_tencent_sign_name'] }}" placeholder="短信签名"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="text" name="sms_tencent_template_id" value="{{ $settings['sms_tencent_template_id'] }}" placeholder="模板 ID"
                               class="rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 col-span-2">
                    </div>
                </div>
                <p class="text-xs text-gray-400 flex items-start gap-1.5">
                    <svg class="w-3.5 h-3.5 mt-0.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    驱动与密钥保存在数据库（settings 表），优先于 .env；生产接入需在服务商控制台开通短信服务。
                </p>
            </x-settings-section>

            {{-- 运行状态（只读）--}}
            <x-settings-section title="运行状态" icon="activity" divider>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 px-3 py-2.5">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">实时服务</p>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> ws:6001
                        </p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 px-3 py-2.5">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">后台前缀</p>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">/{{ config('app.admin_url') }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 px-3 py-2.5">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">短信通道</p>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ \App\Services\SmsService::driver() }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 px-3 py-2.5">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">微信登录</p>
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ \App\Services\WechatService::enabled() ? '真实模式' : '演示模式' }}</p>
                    </div>
                </div>
            </x-settings-section>

            {{-- 浮动保存按钮（表单很长时始终可见） --}}
            <div class="sticky bottom-4 pt-3 flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white shadow-lg ring-1 ring-indigo-700/20 hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    保存设置
                </button>
            </div>
        </form>
    </div>
@endsection
