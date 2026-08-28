<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 测试环境默认不限制工作时间（避免跑测试的时段影响结果）
        Setting::create(['setting_key' => 'work_hours_enabled', 'value' => '0']);
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'password' => bcrypt('password')]);
    }

    public function test_customer_can_create_ticket(): void
    {
        $this->actingAs($this->customer());

        $this->post(route('tickets.store'), [
            'subject' => '测试工单主题',
            'description' => '测试描述内容',
            'priority' => 'normal',
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['subject' => '测试工单主题', 'status' => Ticket::STATUS_OPEN]);
    }

    public function test_customer_blocked_outside_work_hours(): void
    {
        // 工作时间限制为过去时段 → 客户提交被拒
        Setting::updateOrCreate(['setting_key' => 'work_hours_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['setting_key' => 'work_start'], ['value' => '00:00']);
        Setting::updateOrCreate(['setting_key' => 'work_end'], ['value' => '00:01']);

        $this->actingAs($this->customer());

        $this->post(route('tickets.store'), [
            'subject' => '非工作时间提交',
            'description' => '应被拒绝',
            'priority' => 'normal',
        ])->assertSessionHasErrors('subject');

        $this->assertDatabaseMissing('tickets', ['subject' => '非工作时间提交']);
    }

    public function test_agent_can_create_ticket_anytime(): void
    {
        // 同一非工作时间设定下，客服不受限
        Setting::updateOrCreate(['setting_key' => 'work_hours_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['setting_key' => 'work_start'], ['value' => '00:00']);
        Setting::updateOrCreate(['setting_key' => 'work_end'], ['value' => '00:01']);

        $agent = User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
        $this->actingAs($agent);

        $this->post(route('tickets.store'), [
            'subject' => '客服补录',
            'description' => '不受工作时间限制',
            'priority' => 'high',
            'assignee_id' => $agent->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['subject' => '客服补录']);
    }

    public function test_ticket_reply_creates_log_and_notification(): void
    {
        $customer = $this->customer();
        $agent = User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'assignee_id' => $agent->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->actingAs($agent);

        $this->post(route('tickets.reply', $ticket), ['content' => '已收到，正在处理'])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_replies', ['ticket_id' => $ticket->id, 'content' => '已收到，正在处理']);
        $this->assertDatabaseHas('ticket_logs', ['ticket_id' => $ticket->id]);
    }
}
