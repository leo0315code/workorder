<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SupportScanDailyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
    }

    public function test_schedule_is_registered(): void
    {
        // 走 schedule:list 输出断言，避免直接解析 Schedule 单例时受应用引导时序影响
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('support:scan-daily', $output);
        $this->assertStringContainsString('0 9 * * *', $output);
    }

    public function test_command_notifies_on_overdue_sla_ticket(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->create(['role' => 'agent']);

        Ticket::factory()->create([
            'assignee_id' => $agent->id,
            'status' => Ticket::STATUS_OPEN,
            'sla_due_at' => now()->subHour(),
            'subject' => '已超时的工单',
        ]);

        Artisan::call('support:scan-daily');

        $this->assertTrue(
            UserNotification::where('user_id', $agent->id)
                ->where('title', 'like', '%SLA%超时%')
                ->exists(),
            'SLA 超时应通知对应客服'
        );
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $admin->id, 'title' => '有 0 个工单 SLA 已超时']);
    }

    public function test_command_notifies_admin_on_stale_unclaimed_ticket(): void
    {
        $admin = $this->admin();

        Ticket::factory()->create([
            'assignee_id' => null,
            'status' => Ticket::STATUS_OPEN,
            'created_at' => now()->subHours(30),
            'subject' => '待认领超时的工单',
        ]);

        Artisan::call('support:scan-daily');

        $this->assertTrue(
            UserNotification::where('user_id', $admin->id)
                ->where('title', 'like', '%待认领超过 24 小时%')
                ->exists()
        );
    }

    public function test_command_notifies_admin_on_expiring_and_expired_customers(): void
    {
        $admin = $this->admin();

        Customer::create([
            'company' => '临期客户有限公司',
            'contact_name' => '张三',
            'phone' => '13800138000',
            'after_sales_expired_at' => now()->addDays(3),
        ]);
        Customer::create([
            'company' => '已过期客户有限公司',
            'contact_name' => '李四',
            'phone' => '13800138001',
            'after_sales_expired_at' => now()->subDay(),
        ]);

        Artisan::call('support:scan-daily');

        $this->assertTrue(
            UserNotification::where('user_id', $admin->id)->where('title', 'like', '%售后即将到期%')->exists()
        );
        $this->assertTrue(
            UserNotification::where('user_id', $admin->id)->where('title', 'like', '%售后已过期%')->exists()
        );
    }

    public function test_command_is_idempotent_when_nothing_to_do(): void
    {
        $this->admin();

        $exit = Artisan::call('support:scan-daily');

        $this->assertSame(0, $exit);
        $this->assertDatabaseCount('user_notifications', 0);
    }
}
