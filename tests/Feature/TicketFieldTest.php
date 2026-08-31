<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketFieldDef;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFieldTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'password' => bcrypt('password')]);
    }

    private function def(array $attrs = []): TicketFieldDef
    {
        return TicketFieldDef::create(array_merge([
            'label' => '设备序列号',
            'key' => 'serial_no',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
            'sort' => 0,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // 字段定义管理（仅管理员）
    // -------------------------------------------------------------------------

    public function test_only_admin_can_manage_field_defs(): void
    {
        $this->actingAs($this->customer())->get(route('admin.field-defs.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.field-defs.index'))->assertOk();
    }

    public function test_admin_can_create_update_delete_field_def(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.field-defs.store'), [
            'label' => '故障类型',
            'key' => 'fault_type',
            'type' => 'select',
            'options' => '硬件,软件,网络',
        ])->assertRedirect(route('admin.field-defs.index'));

        $def = TicketFieldDef::where('key', 'fault_type')->firstOrFail();
        $this->assertSame(['硬件', '软件', '网络'], $def->options);

        $this->patch(route('admin.field-defs.update', $def), [
            'label' => '故障分类',
            'key' => 'fault_type',
            'type' => 'select',
            'options' => '硬件,软件',
        ])->assertRedirect(route('admin.field-defs.index'));
        $this->assertSame('故障分类', $def->fresh()->label);

        $this->delete(route('admin.field-defs.destroy', $def))->assertRedirect(route('admin.field-defs.index'));
        $this->assertDatabaseMissing('ticket_field_defs', ['id' => $def->id]);
    }

    // -------------------------------------------------------------------------
    // 创建工单时保存字段值
    // -------------------------------------------------------------------------

    public function test_ticket_store_saves_field_values(): void
    {
        $this->def(['key' => 'serial_no', 'label' => '序列号']);
        $user = $this->customer();

        $this->actingAs($user)->post(route('tickets.store'), [
            'subject' => '带字段的工单',
            'description' => '描述',
            'priority' => 'normal',
            'field_serial_no' => 'SN-2026-0001',
        ])->assertRedirect();

        $ticket = Ticket::where('subject', '带字段的工单')->firstOrFail();
        $this->assertDatabaseHas('ticket_field_values', [
            'ticket_id' => $ticket->id,
            'value' => 'SN-2026-0001',
        ]);
    }

    public function test_required_field_blocks_ticket_creation(): void
    {
        $this->def(['key' => 'serial_no', 'label' => '设备序列号', 'is_required' => true]);
        $user = $this->customer();

        $this->actingAs($user)->from(route('tickets.create'))->post(route('tickets.store'), [
            'subject' => '缺字段的工单',
            'description' => '描述',
            'priority' => 'normal',
        ])->assertSessionHasErrors('field_serial_no');

        $this->assertDatabaseMissing('tickets', ['subject' => '缺字段的工单']);
    }

    public function test_inactive_field_is_ignored(): void
    {
        $this->def(['key' => 'old_field', 'is_active' => false, 'is_required' => true]);
        $user = $this->customer();

        // 停用字段即使必填也不拦截
        $this->actingAs($user)->post(route('tickets.store'), [
            'subject' => '正常工单',
            'description' => '描述',
            'priority' => 'normal',
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['subject' => '正常工单']);
    }

    public function test_field_values_shown_on_ticket_detail(): void
    {
        $def = $this->def(['key' => 'serial_no', 'label' => '设备序列号']);
        $user = $this->customer();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $ticket->fieldValues()->create(['field_def_id' => $def->id, 'value' => 'SN-999']);

        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('设备序列号')
            ->assertSee('SN-999');
    }
}
