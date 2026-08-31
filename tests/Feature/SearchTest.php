<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SearchTest：全局搜索：跨工单/客户/产品检索、客户权限隔离、下拉建议相对 URL、分页保留关键词
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'password' => bcrypt('password')]);
    }

    public function test_guest_cannot_search(): void
    {
        $this->get(route('search'))->assertRedirect(route('login'));
    }

    public function test_search_page_renders_empty_state(): void
    {
        $this->actingAs($this->agent());

        $this->get(route('search'))
            ->assertOk()
            ->assertSee('输入关键词开始搜索');
    }

    public function test_agent_can_search_across_tickets_customers_and_products(): void
    {
        $agent = $this->agent();

        $ticket = Ticket::factory()->create([
            'subject' => '登录后台提示验证码错误',
            'description' => '客户无法登录',
        ]);

        Customer::create([
            'company' => '登录科技有限公司',
            'contact_name' => '张三',
            'phone' => '13800138000',
            'email' => 'zs@example.com',
        ]);

        Product::create([
            'name' => '登录网关',
            'sku' => 'GW-LOGIN-01',
            'warranty_days' => 365,
            'is_active' => true,
        ]);

        $response = $this->actingAs($agent)->get(route('search', ['q' => '登录']));

        $response->assertOk()
            ->assertSee('共找到')
            ->assertSee($ticket->no)
            ->assertSee('登录科技有限公司')
            ->assertSee('GW-LOGIN-01');
    }

    public function test_search_shows_no_result_state(): void
    {
        $this->actingAs($this->agent());

        $this->get(route('search', ['q' => '一定不存在的关键字ZZZ']))
            ->assertOk()
            ->assertSee('没有找到匹配的内容');
    }

    public function test_customer_can_only_search_own_tickets(): void
    {
        $me = $this->customer();
        $other = $this->customer();

        $mine = Ticket::factory()->create([
            'user_id' => $me->id,
            'subject' => '登录异常排查',
        ]);
        Ticket::factory()->create([
            'user_id' => $other->id,
            'subject' => '登录异常排查他人的工单',
        ]);

        Customer::create([
            'company' => '登录科技有限公司',
            'contact_name' => '张三',
            'phone' => '13800138000',
        ]);

        $response = $this->actingAs($me)->get(route('search', ['q' => '登录']));

        $response->assertOk()
            ->assertSee($mine->no);

        // 客户不应看到「客户档案」「产品」两个分区，也不应看到他人工单
        $response->assertDontSee('客户档案（');
        $response->assertDontSee('产品（');
        $this->assertStringNotContainsString(
            '登录异常排查他人的工单',
            $response->getContent()
        );
    }

    public function test_suggest_returns_relative_urls(): void
    {
        $ticket = Ticket::factory()->create(['subject' => '登录后台报错']);

        $response = $this->actingAs($this->agent())
            ->getJson(route('search.suggest', ['q' => '登录']));

        $response->assertOk()->assertJsonStructure(['items']);

        $items = $response->json('items');
        $this->assertNotEmpty($items);
        $this->assertSame('/tickets/'.$ticket->id, $items[0]['url']);
    }

    public function test_suggest_returns_empty_for_blank_keyword(): void
    {
        $this->actingAs($this->agent())
            ->getJson(route('search.suggest', ['q' => '']))
            ->assertOk()
            ->assertExactJson(['items' => []]);
    }

    public function test_pagination_links_preserve_keyword(): void
    {
        Ticket::factory()->count(12)->create(['subject' => '打印机无法出纸']);

        $response = $this->actingAs($this->agent())
            ->get(route('search', ['q' => '打印机']));

        $response->assertOk();

        $this->assertStringContainsString(
            'q='.urlencode('打印机'),
            $response->getContent()
        );
    }
}
