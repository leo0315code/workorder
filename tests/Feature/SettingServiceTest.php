<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MenuService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SettingServiceTest + MenuServiceTest：系统配置读写与侧边栏菜单渲染
 */
class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    // -------------------------------------------------------------------------
    // SettingService
    // -------------------------------------------------------------------------

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertNull(SettingService::get('no_such_key'));
        $this->assertSame('fallback', SettingService::get('no_such_key', 'fallback'));
    }

    public function test_set_and_get_roundtrip(): void
    {
        SettingService::set('sla_high', '36');
        $this->assertSame('36', SettingService::get('sla_high'));
    }

    public function test_set_many_skips_null(): void
    {
        SettingService::setMany(['sla_low' => '48', 'sla_normal' => null]);

        $this->assertSame('48', SettingService::get('sla_low'));
        $this->assertNull(SettingService::get('sla_normal'));
    }

    public function test_site_name_falls_back_to_app_name(): void
    {
        Setting::where('setting_key', 'site_name')->delete();
        $this->assertSame(config('app.name'), SettingService::siteName());
    }

    public function test_sla_hours_uses_defaults_when_unconfigured(): void
    {
        $hours = SettingService::slaHours();

        $this->assertSame(Ticket::$slaHours[Ticket::PRIORITY_LOW], $hours[Ticket::PRIORITY_LOW]);
        $this->assertSame(Ticket::$slaHours[Ticket::PRIORITY_URGENT], $hours[Ticket::PRIORITY_URGENT]);
    }

    public function test_auto_assign_enabled_defaults_true(): void
    {
        $this->assertTrue(SettingService::autoAssignEnabled());

        Setting::updateOrCreate(['setting_key' => 'auto_assign'], ['value' => '0']);
        $this->assertFalse(SettingService::autoAssignEnabled());
    }

    // -------------------------------------------------------------------------
    // MenuService
    // -------------------------------------------------------------------------

    private function menu(array $attrs = []): Menu
    {
        return Menu::create(array_merge([
            'audience' => Menu::AUDIENCE_AGENT,
            'label' => '工单',
            'route_name' => 'tickets.index',
            'icon' => 'ticket',
            'sort' => 1,
            'is_active' => true,
        ], $attrs));
    }

    public function test_sidebar_filters_by_audience_and_sort(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->menu(['label' => '客服菜单', 'route_name' => 'dashboard']);
        $this->menu(['label' => '客户菜单', 'audience' => Menu::AUDIENCE_CUSTOMER, 'route_name' => 'dashboard']);

        $agentMenus = MenuService::sidebarFor($agent);
        $customerMenus = MenuService::sidebarFor($customer);

        $this->assertCount(1, $agentMenus);
        $this->assertSame('客服菜单', $agentMenus[0]['label']);
        $this->assertCount(1, $customerMenus);
        $this->assertSame('客户菜单', $customerMenus[0]['label']);
    }

    public function test_sidebar_hides_admin_only_from_agent(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->menu(['label' => '仅管理员', 'route_name' => 'dashboard', 'admin_only' => true]);
        $this->menu(['label' => '公共', 'route_name' => 'dashboard']);

        $agentLabels = array_column(MenuService::sidebarFor($agent), 'label');
        $adminLabels = array_column(MenuService::sidebarFor($admin), 'label');

        $this->assertNotContains('仅管理员', $agentLabels);
        $this->assertContains('仅管理员', $adminLabels);
    }

    public function test_sidebar_skips_menu_with_missing_route(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $this->menu(['label' => '死链菜单', 'route_name' => 'no.such.route']);

        $this->assertEmpty(MenuService::sidebarFor($agent));
    }

    public function test_sidebar_returns_empty_for_guest(): void
    {
        $this->assertSame([], MenuService::sidebarFor(null));
    }

    public function test_sidebar_active_flags_index_route(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $this->menu(['label' => '工单列表', 'route_name' => 'tickets.index']);

        Route::get('/tickets', fn () => 'ok')->name('tickets.index');

        $this->get('/tickets');
        $items = MenuService::sidebarFor($agent);

        $this->assertTrue($items[0]['active']);
    }
}
