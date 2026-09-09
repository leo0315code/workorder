<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 测试环境默认禁用工作时间限制，避免套件结果随当前时刻变化（18:00 后跑测试会挂的定时炸弹）
        // 需要验证工作时间逻辑的用例请显式 updateOrCreate 覆盖
        if (Schema::hasTable('settings')) {
            Setting::firstOrCreate(['setting_key' => 'work_hours_enabled'], ['value' => '0']);
        }
    }
}
