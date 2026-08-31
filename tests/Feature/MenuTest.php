<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
    }

    private function agentWithModules(array $modules): User
    {
        return User::factory()->create(['role' => 'agent', 'permissions' => $modules, 'password' => bcrypt('password')]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'password' => bcrypt('password')]);
    }

    private function menu(array $attrs = []): Menu
    {
        return Menu::create(array_merge([
            'audience' => 'agent',
            'admin_only' => false,
            'label' => '测试菜单',
            'route_name' => 'dashboard',
            'icon' => 'ticket',
            'module' => null,
            'sort' => 1,
            'is_active' => true,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // 权限
    // -------------------------------------------------------------------------

    public function test_only_admin_can_manage_menus(): void
    {
        $this->actingAs($this->agent())->get(route('admin.menus.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.menus.index'))->assertOk();
    }

    // -------------------------------------------------------------------------
    // 管理 CRUD
    // -------------------------------------------------------------------------

    public function test_admin_can_create_update_and_delete_menu(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.menus.store'), [
            'label' => '知识库',
            'route_name' => 'admin.knowledge.index',
            'audience' => 'agent',
            'icon' => 'list',
            'module' => '',
            'sort' => 5,
            'is_active' => 1,
        ])->assertRedirect(route('admin.menus.index'));

        $menu = Menu::where('label', '知识库')->firstOrFail();
        $this->assertSame('admin.knowledge.index', $menu->route_name);
        $this->assertTrue($menu->is_active);

        $this->patch(route('admin.menus.update', $menu), [
            'label' => '知识库 v2',
            'route_name' => 'admin.knowledge.index',
            'audience' => 'agent',
            'icon' => 'list',
            'module' => '',
            'sort' => 6,
        ])->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'label' => '知识库 v2', 'sort' => 6]);

        $this->delete(route('admin.menus.destroy', $menu))->assertRedirect(route('admin.menus.index'));
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }

    // -------------------------------------------------------------------------
    // 渲染过滤
    // -------------------------------------------------------------------------

    public function test_sidebar_filters_by_audience(): void
    {
        $this->menu(['audience' => 'agent', 'label' => '客服专属']);
        $this->menu(['audience' => 'customer', 'label' => '客户专属']);

        $this->actingAs($this->agent())->get(route('dashboard'))->assertSee('客服专属')->assertDontSee('客户专属');
        $this->actingAs($this->customer())->get(route('dashboard'))->assertSee('客户专属')->assertDontSee('客服专属');
    }

    public function test_sidebar_filters_by_module_permission(): void
    {
        $this->menu(['label' => '客户档案入口', 'module' => 'customers']);

        // 有 customers 权限 → 可见
        $this->actingAs($this->agentWithModules(['customers']))->get(route('dashboard'))->assertSee('客户档案入口');
        // 只有 products 权限 → 不可见
        $this->actingAs($this->agentWithModules(['products']))->get(route('dashboard'))->assertDontSee('客户档案入口');
    }

    public function test_sidebar_filters_by_admin_only(): void
    {
        $this->menu(['label' => '仅管理员菜单', 'admin_only' => true]);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertSee('仅管理员菜单');
        $this->actingAs($this->agent())->get(route('dashboard'))->assertDontSee('仅管理员菜单');
    }

    public function test_sidebar_hides_inactive_menu(): void
    {
        $this->menu(['label' => '停用菜单', 'is_active' => false]);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertDontSee('停用菜单');
    }

    public function test_sidebar_skips_menu_with_missing_route(): void
    {
        // 路由名不存在 → 自动跳过（防死链）
        $this->menu(['label' => '死链菜单', 'route_name' => 'admin.not-exist.index']);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertDontSee('死链菜单');
    }

    public function test_menu_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\MenuSeeder::class);
        $first = Menu::count();
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $this->assertSame($first, Menu::count());
        $this->assertSame(15, $first); // 13 客服端（含知识库）+ 2 客户端
    }

    public function test_sidebar_groups_items_by_section(): void
    {
        // 构造一组有明显分块的两条菜单
        $this->menu(['audience' => 'agent', 'label' => '工作台', 'route_name' => 'dashboard', 'section' => '概览', 'sort' => 1]);
        $this->menu(['audience' => 'agent', 'label' => '数据', 'route_name' => 'admin.reports', 'section' => '运营', 'sort' => 50]);

        $body = $this->actingAs($this->admin())->get(route('dashboard'))->getContent();
        // 小标题出现
        $this->assertStringContainsString('概览', $body);
        $this->assertStringContainsString('运营', $body);
        // 同组的 概览 出现在 运营 之前
        $this->assertLessThan(strpos($body, '运营'), strpos($body, '概览'));
    }

    // -------------------------------------------------------------------------
    // 单一字段快速更新（内联下拉）
    // -------------------------------------------------------------------------

    public function test_update_field_changes_audience(): void
    {
        $menu = $this->menu(['audience' => 'agent', 'label' => '切换测试']);

        $this->actingAs($this->admin())
            ->post(route('admin.menus.update-field', $menu), ['field' => 'audience', 'value' => 'customer'])
            ->assertRedirect();

        $this->assertSame('customer', $menu->fresh()->audience);
    }

    public function test_update_field_toggles_is_active(): void
    {
        $menu = $this->menu(['is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.menus.update-field', $menu), ['field' => 'is_active', 'value' => '0'])
            ->assertRedirect();

        $this->assertFalse($menu->fresh()->is_active);
    }

    public function test_update_field_toggles_admin_only(): void
    {
        $menu = $this->menu(['admin_only' => false]);

        $this->actingAs($this->admin())
            ->post(route('admin.menus.update-field', $menu), ['field' => 'admin_only', 'value' => '1'])
            ->assertRedirect();

        $this->assertTrue($menu->fresh()->admin_only);
    }

    public function test_update_field_rejects_unauthorized_field(): void
    {
        $menu = $this->menu(['label' => '原始']);

        $this->actingAs($this->admin())
            ->post(route('admin.menus.update-field', $menu), ['field' => 'label', 'value' => '被劫持'])
            ->assertSessionHasErrors('field');

        $this->assertSame('原始', $menu->fresh()->label);
    }
}
