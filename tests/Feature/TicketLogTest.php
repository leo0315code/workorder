<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent']);
    }

    private function log(User $user, string $action, array $extra = []): TicketLog
    {
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        return TicketLog::create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'action' => $action,
            'note' => '测试日志',
        ], $extra));
    }

    public function test_admin_can_view_ticket_logs(): void
    {
        $admin = $this->admin();
        $log = $this->log($admin, 'created');

        $this->actingAs($admin)
            ->get(route('admin.ticket-logs.index'))
            ->assertOk()
            ->assertSee($log->ticket->no);
    }

    public function test_agent_cannot_access_ticket_logs(): void
    {
        $this->actingAs($this->agent())
            ->get(route('admin.ticket-logs.index'))
            ->assertForbidden();
    }

    public function test_filters_by_user(): void
    {
        $admin = $this->admin();
        $other = $this->agent();
        $mine = $this->log($admin, 'created');
        $target = $this->log($other, 'noted');

        $response = $this->actingAs($admin)
            ->get(route('admin.ticket-logs.index', ['user_id' => $other->id]))
            ->assertOk();

        // 只显示该操作人的日志
        $html = $response->getContent();
        $this->assertStringContainsString($target->ticket->no, $html);
        $this->assertStringNotContainsString($mine->ticket->no, $html);
    }

    public function test_filters_by_action(): void
    {
        $admin = $this->admin();
        $created = $this->log($admin, 'created');
        $noted = $this->log($admin, 'noted');

        $response = $this->actingAs($admin)
            ->get(route('admin.ticket-logs.index', ['action' => 'noted']))
            ->assertOk();

        // 只出现 noted 对应工单，created 对应工单被过滤（下拉选项本身含全部文案，不能用徽标文案断言）
        $html = $response->getContent();
        $this->assertStringContainsString($noted->ticket->no, $html);
        $this->assertStringNotContainsString($created->ticket->no, $html);
    }

    public function test_filters_by_keyword_ticket_no(): void
    {
        $admin = $this->admin();
        $log = $this->log($admin, 'closed');

        $this->actingAs($admin)
            ->get(route('admin.ticket-logs.index', ['q' => $log->ticket->no]))
            ->assertOk()
            ->assertSee($log->ticket->no);
    }

    public function test_filters_by_date_range(): void
    {
        $admin = $this->admin();
        $inside = $this->log($admin, 'created');
        $inside->forceFill(['created_at' => now()->subDay()])->save();
        $outside = $this->log($admin, 'closed');
        $outside->forceFill(['created_at' => now()->subDays(10)])->save();

        $from = now()->subDays(2)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->actingAs($admin)
            ->get(route('admin.ticket-logs.index', ['from' => $from, 'to' => $to]))
            ->assertOk();

        $this->assertStringContainsString($inside->ticket->no, $response->getContent());
        $this->assertStringNotContainsString($outside->ticket->no, $response->getContent());
    }
}
