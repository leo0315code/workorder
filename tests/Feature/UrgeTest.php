<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrgeTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent']);
    }

    public function test_customer_can_urge_own_open_ticket(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assignee_id' => $agent->id, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($customer)
            ->post(route('tickets.urge', $ticket))
            ->assertRedirect();

        // 通知负责人 + 操作日志
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $agent->id,
            'title' => '客户催办工单',
        ]);
        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'urged',
        ]);
    }

    public function test_urge_rate_limited_to_once_per_24h(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assignee_id' => $agent->id, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($customer)->post(route('tickets.urge', $ticket))->assertRedirect();
        $this->actingAs($customer)->post(route('tickets.urge', $ticket))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, $ticket->logs()->where('action', 'urged')->count());
    }

    public function test_cannot_urge_resolved_ticket(): void
    {
        $customer = $this->customer();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => Ticket::STATUS_RESOLVED]);

        $this->actingAs($customer)
            ->post(route('tickets.urge', $ticket))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, $ticket->logs()->where('action', 'urged')->count());
    }

    public function test_other_customer_cannot_urge(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($other)
            ->post(route('tickets.urge', $ticket))
            ->assertForbidden();
    }

    public function test_agent_cannot_urge(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($agent)
            ->post(route('tickets.urge', $ticket))
            ->assertForbidden();
    }

    public function test_urge_notifies_all_agents_when_unassigned(): void
    {
        $customer = $this->customer();
        $agent = $this->agent();
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assignee_id' => null, 'status' => Ticket::STATUS_OPEN]);

        $this->actingAs($customer)->post(route('tickets.urge', $ticket))->assertRedirect();

        $this->assertSame(2, UserNotification::where('title', '客户催办工单（未指派）')->count());
        $this->assertDatabaseHas('user_notifications', ['user_id' => $agent->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $admin->id]);
    }
}
