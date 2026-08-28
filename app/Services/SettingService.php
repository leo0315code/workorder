<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

/**
 * 系统配置（settings 表，运行时修改即时生效）
 */
class SettingService
{
    /**
     * 读取配置；未设置时返回默认值
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = Setting::where('setting_key', $key)->first();

        return $row?->value ?? $default;
    }

    /**
     * 写入配置
     */
    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['setting_key' => $key], ['value' => (string) $value]);
    }

    /**
     * 批量写入（表单保存用）
     */
    public static function setMany(array $items): void
    {
        foreach ($items as $key => $value) {
            if ($value !== null) {
                self::set($key, $value);
            }
        }
    }

    /**
     * 网站名称（用于顶栏/登录页）
     */
    public static function siteName(): string
    {
        return (string) (self::get('site_name') ?: config('app.name'));
    }

    /**
     * 各优先级 SLA 小时数（配置可覆盖默认值）
     */
    public static function slaHours(): array
    {
        return [
            \App\Models\Ticket::PRIORITY_LOW => (int) self::get('sla_low', \App\Models\Ticket::$slaHours[\App\Models\Ticket::PRIORITY_LOW]),
            \App\Models\Ticket::PRIORITY_NORMAL => (int) self::get('sla_normal', \App\Models\Ticket::$slaHours[\App\Models\Ticket::PRIORITY_NORMAL]),
            \App\Models\Ticket::PRIORITY_HIGH => (int) self::get('sla_high', \App\Models\Ticket::$slaHours[\App\Models\Ticket::PRIORITY_HIGH]),
            \App\Models\Ticket::PRIORITY_URGENT => (int) self::get('sla_urgent', \App\Models\Ticket::$slaHours[\App\Models\Ticket::PRIORITY_URGENT]),
        ];
    }

    /**
     * 自动分配开关
     */
    public static function autoAssignEnabled(): bool
    {
        return self::get('auto_assign', '1') === '1';
    }

    /**
     * 工作时间限制开关
     */
    public static function workTimeEnabled(): bool
    {
        return self::get('work_hours_enabled', '1') === '1';
    }

    /**
     * 当前是否处于上班时间（工作日 + 时段内）
     */
    public static function isWorkTime(?\Illuminate\Support\Carbon $now = null): bool
    {
        if (! self::workTimeEnabled()) {
            return true;
        }

        $now = $now ?: now();

        // 工作日（1=周一 … 7=周日）
        $days = array_values(array_filter(array_map('trim', explode(',', (string) self::get('work_days', '1,2,3,4,5')))));
        if (! in_array((string) $now->isoWeekday(), $days, true)) {
            return false;
        }

        $time = $now->format('H:i');
        $start = (string) self::get('work_start', '09:00');
        $end = (string) self::get('work_end', '18:00');

        return $time >= $start && $time < $end;
    }

    /**
     * 工作时间文案（用于提示）
     */
    public static function workHoursText(): string
    {
        $days = (string) self::get('work_days', '1,2,3,4,5');
        $dayNames = ['1' => '周一', '2' => '周二', '3' => '周三', '4' => '周四', '5' => '周五', '6' => '周六', '7' => '周日'];
        $labels = array_map(fn ($d) => $dayNames[$d] ?? $d, array_filter(explode(',', $days)));

        return implode('、', $labels).' '.self::get('work_start', '09:00').' - '.self::get('work_end', '18:00');
    }
}
