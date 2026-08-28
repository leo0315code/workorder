<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'site_name' => SettingService::get('site_name', config('app.name')),
            'auto_assign' => SettingService::get('auto_assign', '1'),
            'sla_low' => SettingService::get('sla_low', 72),
            'sla_normal' => SettingService::get('sla_normal', 48),
            'sla_high' => SettingService::get('sla_high', 24),
            'sla_urgent' => SettingService::get('sla_urgent', 8),
            'work_hours_enabled' => SettingService::get('work_hours_enabled', '1'),
            'work_start' => SettingService::get('work_start', '09:00'),
            'work_end' => SettingService::get('work_end', '18:00'),
            'work_days' => SettingService::get('work_days', '1,2,3,4,5'),
            'email_notify_enabled' => SettingService::get('email_notify_enabled', '0'),
            // 短信通道（运行时配置，兜底 .env）
            'sms_driver' => SmsService::driver(),
            'sms_allow_demo_code' => (string) (SmsService::cfg('allow_demo_code', true) ? '1' : '0'),
            'sms_aliyun_access_key_id' => (string) SettingService::get('sms_aliyun_access_key_id', ''),
            'sms_aliyun_access_key_secret' => (string) SettingService::get('sms_aliyun_access_key_secret', ''),
            'sms_aliyun_sign_name' => (string) SettingService::get('sms_aliyun_sign_name', ''),
            'sms_aliyun_template_code' => (string) SettingService::get('sms_aliyun_template_code', ''),
            'sms_tencent_secret_id' => (string) SettingService::get('sms_tencent_secret_id', ''),
            'sms_tencent_secret_key' => (string) SettingService::get('sms_tencent_secret_key', ''),
            'sms_tencent_sdk_app_id' => (string) SettingService::get('sms_tencent_sdk_app_id', ''),
            'sms_tencent_sign_name' => (string) SettingService::get('sms_tencent_sign_name', ''),
            'sms_tencent_template_id' => (string) SettingService::get('sms_tencent_template_id', ''),
        ];

        return view('settings.index', compact('settings'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:50'],
            'auto_assign' => ['nullable', 'boolean'],
            'sla_low' => ['required', 'integer', 'min:1', 'max:720'],
            'sla_normal' => ['required', 'integer', 'min:1', 'max:720'],
            'sla_high' => ['required', 'integer', 'min:1', 'max:720'],
            'sla_urgent' => ['required', 'integer', 'min:1', 'max:720'],
            'work_hours_enabled' => ['nullable', 'boolean'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i'],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['in:1,2,3,4,5,6,7'],
            'sms_driver' => ['required', 'in:demo,aliyun,tencent'],
            'sms_allow_demo_code' => ['nullable', 'boolean'],
            'email_notify_enabled' => ['nullable', 'boolean'],
        ]);

        SettingService::setMany([
            'site_name' => $data['site_name'],
            'auto_assign' => $request->boolean('auto_assign') ? '1' : '0',
            'sla_low' => $data['sla_low'],
            'sla_normal' => $data['sla_normal'],
            'sla_high' => $data['sla_high'],
            'sla_urgent' => $data['sla_urgent'],
            'work_hours_enabled' => $request->boolean('work_hours_enabled') ? '1' : '0',
            'work_start' => $data['work_start'],
            'work_end' => $data['work_end'],
            'work_days' => implode(',', $data['work_days']),
            'sms_driver' => $data['sms_driver'],
            'sms_allow_demo_code' => $request->boolean('sms_allow_demo_code') ? '1' : '0',
            'email_notify_enabled' => $request->boolean('email_notify_enabled') ? '1' : '0',
        ]);

        // 短信密钥类字段：仅在非空时覆盖（避免每次保存清空已有密钥）
        foreach ([
            'sms_aliyun_access_key_id', 'sms_aliyun_access_key_secret', 'sms_aliyun_sign_name', 'sms_aliyun_template_code',
            'sms_tencent_secret_id', 'sms_tencent_secret_key', 'sms_tencent_sdk_app_id', 'sms_tencent_sign_name', 'sms_tencent_template_id',
        ] as $key) {
            if ($request->filled($key)) {
                SettingService::set($key, $request->input($key));
            }
        }

        return redirect()->route('admin.settings')->with('success', '系统设置已保存');
    }
}
