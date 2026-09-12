<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SearchServiceTest：全局搜索（工单/客户/产品）
 * - 权限：客服可搜全部；客户仅搜自己的工单，客户/产品返回空
 * - tickets/customers/products/search/suggest
 */
class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent']);
    }

    // -------------------------------------------------------------------------
    // tickets
    // -------------------------------------------------------------------------

    public function test_agent_searches_all_tickets(): void
    {
        $agent = $this->agent();
        $c1 = $this->customer();
        $c2 = $this->customer();
        Ticket::factory()->create(['user_id' => $c1->id, 'subject' => '打印机卡纸']);
        Ticket::factory()->create(['user_id' => $c2->id, 'subject' => '打印机卡纸']);

        $result = SearchService::tickets($agent, '卡纸');

        $this->assertSame(2, $result->total());
    }

    public function test_customer_only_searches_own_tickets(): void
    {
        $me = $this->customer();
        $other = $this->customer();
        Ticket::factory()->create(['user_id' => $me->id, 'subject' => '打印机卡纸']);
        Ticket::factory()->create(['user_id' => $other->id, 'subject' => '打印机卡纸']);

        $result = SearchService::tickets($me, '卡纸');

        $this->assertSame(1, $result->total());
        $this->assertSame($me->id, $result->items()[0]->user_id);
    }

    public function test_search_by_no_and_description(): void
    {
        $agent = $this->agent();
        $c = $this->customer();
        $t = Ticket::factory()->create(['user_id' => $c->id, 'no' => 'TK-20260912-9999', 'description' => '电源指示灯不亮']);

        $this->assertSame(1, SearchService::tickets($agent, 'TK-20260912')->total());
        $this->assertSame(1, SearchService::tickets($agent, '指示灯')->total());
        $this->assertSame($t->id, SearchService::tickets($agent, 'TK-20260912')->items()[0]->id);
    }

    // -------------------------------------------------------------------------
    // customers / products
    // -------------------------------------------------------------------------

    public function test_agent_searches_customers_and_products(): void
    {
        $agent = $this->agent();
        Customer::create(['company' => '华信医疗', 'contact_name' => '张工', 'phone' => '13800138000']);
        Product::create(['name' => '一体机', 'sku' => 'SKU-100']);

        $this->assertSame(1, SearchService::customers($agent, '华信')->total());
        $this->assertSame(1, SearchService::customers($agent, '张工')->total());
        $this->assertSame(1, SearchService::products($agent, '一体机')->total());
        $this->assertSame(1, SearchService::products($agent, 'SKU-100')->total());
    }

    public function test_customer_gets_empty_customers_and_products(): void
    {
        $customer = $this->customer();
        Customer::create(['company' => '华信医疗', 'contact_name' => '张工', 'phone' => '13800138000']);

        $this->assertSame(0, SearchService::customers($customer, '华信')->total());
        $this->assertSame(0, SearchService::products($customer, '一体机')->total());
    }

    // -------------------------------------------------------------------------
    // search / suggest
    // -------------------------------------------------------------------------

    public function test_search_returns_three_sections(): void
    {
        $agent = $this->agent();
        $c = $this->customer();
        Ticket::factory()->create(['user_id' => $c->id, 'subject' => '一体机故障']);

        $result = SearchService::search($agent, '一体机');

        $this->assertArrayHasKey('tickets', $result);
        $this->assertArrayHasKey('customers', $result);
        $this->assertArrayHasKey('products', $result);
    }

    public function test_suggest_returns_ticket_only_for_customer(): void
    {
        $me = $this->customer();
        $other = $this->customer();
        Ticket::factory()->create(['user_id' => $me->id, 'subject' => '键盘失灵']);
        Ticket::factory()->create(['user_id' => $other->id, 'subject' => '键盘失灵']);

        $items = SearchService::suggest($me, '键盘');

        $types = array_column($items, 'type');
        $this->assertContains('ticket', $types);
        $this->assertNotContains('customer', $types);
        $this->assertNotContains('product', $types);
        $this->assertCount(1, $items);
    }

    public function test_suggest_includes_customer_and_product_for_agent(): void
    {
        $agent = $this->agent();
        $c = $this->customer();
        Ticket::factory()->create(['user_id' => $c->id, 'subject' => '华信一体机故障']);
        Customer::create(['company' => '华信医疗', 'contact_name' => '张工', 'phone' => '13800138000']);
        Product::create(['name' => '华信鼠标', 'sku' => 'SKU-200']);

        $items = SearchService::suggest($agent, '华信');

        $types = array_column($items, 'type');
        $this->assertContains('ticket', $types);
        $this->assertContains('customer', $types);
        $this->assertContains('product', $types);
    }
}
