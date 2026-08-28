<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_backend(): void
    {
        $this->get(route('admin.customers.index'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_backend(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_agent_can_access_admin_backend(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $this->actingAs($agent)
            ->get(route('admin.customers.index'))
            ->assertOk();
    }

    public function test_agent_cannot_access_user_management(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $this->actingAs($agent)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_user_management_and_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertOk();
    }
}
