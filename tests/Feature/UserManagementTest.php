<?php

namespace Tests\Feature;

use App\Models\AgentRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => '新客服小李',
            'email' => 'newagent@example.com',
            'phone' => '13800138000',
            'role' => 'agent',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    public function test_guest_cannot_create_user(): void
    {
        $this->post(route('admin.users.store'), $this->payload())
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'agent']))
            ->post(route('admin.users.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_admin_can_create_agent(): void
    {
        $role = AgentRole::create(['name' => 'supervisor', 'label' => '客服主管', 'modules' => ['customers'], 'is_active' => true, 'sort' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['agent_role_id' => $role->id]))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'newagent@example.com')->firstOrFail();

        $this->assertSame('新客服小李', $user->name);
        $this->assertSame('13800138000', $user->phone);
        $this->assertSame('agent', $user->role);
        $this->assertSame($role->id, $user->agent_role_id);
        $this->assertTrue(Hash::check('secret123', $user->password));
        // 后台创建的用户直接可登录，不阻塞在邮箱验证
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_can_create_customer_and_admin(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['email' => 'c1@example.com', 'role' => 'customer', 'phone' => '13800138001']))
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['email' => 'a1@example.com', 'role' => 'admin', 'phone' => '13800138002']))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'c1@example.com', 'role' => 'customer']);
        $this->assertDatabaseHas('users', ['email' => 'a1@example.com', 'role' => 'admin']);
    }

    public function test_customer_is_never_assigned_agent_role(): void
    {
        $role = AgentRole::create(['name' => 'supervisor', 'label' => '客服主管', 'modules' => ['customers'], 'is_active' => true, 'sort' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['role' => 'customer', 'agent_role_id' => $role->id]))
            ->assertRedirect();

        $user = User::where('email', 'newagent@example.com')->firstOrFail();
        $this->assertNull($user->agent_role_id);
    }

    public function test_admin_can_create_user_with_phone_only(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => '只有手机号的客户',
                'email' => null,
                'phone' => '13900139000',
                'role' => 'customer',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $user = User::where('phone', '13900139000')->firstOrFail();

        $this->assertNull($user->email);
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_blank_email_and_phone_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['email' => '', 'phone' => '']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['name' => '新客服小李']);
    }

    public function test_multiple_users_may_have_null_email(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['email' => '', 'phone' => '13900139001']))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['name' => '另一个', 'email' => '', 'phone' => '13900139002']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, User::whereNull('email')->count());
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'newagent@example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload())
            ->assertSessionHasErrors('email');
    }

    public function test_phone_must_be_unique_and_well_formed(): void
    {
        User::factory()->create(['phone' => '13800138000']);

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload())
            ->assertSessionHasErrors('phone');

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['email' => 'x@example.com', 'phone' => '12345']))
            ->assertSessionHasErrors('phone');
    }

    public function test_password_must_be_confirmed_and_long_enough(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');
    }

    public function test_role_must_be_valid(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), $this->payload(['role' => 'superuser']))
            ->assertSessionHasErrors('role');
    }

    public function test_agent_role_id_can_be_persisted(): void
    {
        // 回归：agent_role_id 曾缺失于 $fillable，导致客服角色分配静默失效
        $role = AgentRole::create(['name' => 'supervisor', 'label' => '客服主管', 'modules' => ['customers'], 'is_active' => true, 'sort' => 1]);
        $agent = User::factory()->create(['role' => 'agent']);

        $this->actingAs($this->admin())
            ->patch(route('admin.users.update-agent-role', $agent), ['agent_role_id' => $role->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($role->id, $agent->fresh()->agent_role_id);
    }

    public function test_agent_role_id_can_be_cleared(): void
    {
        $role = AgentRole::create(['name' => 'supervisor', 'label' => '客服主管', 'modules' => ['customers'], 'is_active' => true, 'sort' => 1]);
        $agent = User::factory()->create(['role' => 'agent', 'agent_role_id' => $role->id]);

        $this->actingAs($this->admin())
            ->patch(route('admin.users.update-agent-role', $agent), ['agent_role_id' => null])
            ->assertRedirect();

        $this->assertNull($agent->fresh()->agent_role_id);
    }

    public function test_users_index_can_filter_by_role_and_keyword(): void
    {
        User::factory()->create(['role' => 'agent', 'name' => '张三丰']);
        User::factory()->create(['role' => 'customer', 'name' => '李四']);

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['q' => '张三丰']))
            ->assertOk()
            ->assertSee('张三丰')
            ->assertDontSee('李四');

        $this->actingAs($this->admin())
            ->get(route('admin.users.index', ['role' => 'customer']))
            ->assertOk()
            ->assertSee('李四')
            ->assertDontSee('张三丰');
    }

    public function test_users_index_renders_create_button(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('新增用户')
            ->assertSee(route('admin.users.store'), false);
    }
}
