<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\QuickReply;
use App\Models\Ticket;
use App\Models\TicketTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminModuleCrudTest：后台模块 CRUD：分类/快捷回复/工单模板增删改查、报表页与 CSV 导出、模块权限隔离
 */
class AdminModuleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
    }

    private function agentWithModules(array $modules): User
    {
        return User::factory()->create(['role' => 'agent', 'permissions' => $modules, 'password' => bcrypt('password')]);
    }

    // -------------------------------------------------------------------------
    // 分类管理（回归：controller 曾用 route('categories.index')，路由名是 admin.categories.*，会 500）
    // -------------------------------------------------------------------------

    public function test_category_store_redirects_to_admin_index(): void
    {
        $this->actingAs($this->agentWithModules(['categories']))
            ->post(route('admin.categories.store'), [
                'name' => '网络问题',
                'description' => '网络连接相关',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['name' => '网络问题', 'is_active' => true]);
    }

    public function test_category_update_and_destroy(): void
    {
        $category = Category::create(['name' => '旧分类', 'slug' => 'old', 'is_active' => true]);
        $this->actingAs($this->agentWithModules(['categories']));

        $this->patch(route('admin.categories.update', $category), ['name' => '新分类', 'is_active' => false])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => '新分类', 'is_active' => false]);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_requires_module_permission(): void
    {
        $this->actingAs($this->agentWithModules(['quick-replies']))
            ->post(route('admin.categories.store'), ['name' => '越权分类'])
            ->assertForbidden();
    }

    public function test_category_index_renders(): void
    {
        Category::create(['name' => '网络问题', 'slug' => 'network', 'is_active' => true]);

        $this->actingAs($this->agentWithModules(['categories']))
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('网络问题');
    }

    // -------------------------------------------------------------------------
    // 快捷回复
    // -------------------------------------------------------------------------

    public function test_quick_reply_crud(): void
    {
        $this->actingAs($this->agentWithModules(['quick-replies']));

        $this->post(route('admin.quick-replies.store'), [
            'title' => '标准问候',
            'content' => '您好，我是客服小王，很高兴为您服务。',
        ])->assertRedirect(route('admin.quick-replies.index'));

        $reply = QuickReply::where('title', '标准问候')->firstOrFail();
        $this->assertTrue($reply->is_active);

        $this->patch(route('admin.quick-replies.update', $reply), [
            'title' => '标准问候 v2',
            'content' => '更新后的内容',
        ])->assertRedirect(route('admin.quick-replies.index'));

        $this->assertDatabaseHas('quick_replies', ['id' => $reply->id, 'title' => '标准问候 v2']);

        $this->delete(route('admin.quick-replies.destroy', $reply))
            ->assertRedirect(route('admin.quick-replies.index'));

        $this->assertDatabaseMissing('quick_replies', ['id' => $reply->id]);
    }

    public function test_quick_reply_requires_title_and_content(): void
    {
        $this->actingAs($this->agentWithModules(['quick-replies']))
            ->post(route('admin.quick-replies.store'), ['title' => '', 'content' => ''])
            ->assertSessionHasErrors(['title', 'content']);
    }

    // -------------------------------------------------------------------------
    // 工单模板
    // -------------------------------------------------------------------------

    public function test_ticket_template_crud(): void
    {
        $this->actingAs($this->agentWithModules(['templates']));

        $this->post(route('admin.ticket-templates.store'), [
            'name' => '电脑无法开机',
            'subject' => '[开机失败] {客户姓名} 反馈无法开机',
            'description' => '请客户提供主机型号与故障视频',
            'priority' => 'normal',
            'sort' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.ticket-templates.index'));

        $template = TicketTemplate::where('name', '电脑无法开机')->firstOrFail();
        $this->assertSame('normal', $template->priority);
        $this->assertTrue($template->is_active);

        $this->patch(route('admin.ticket-templates.update', $template), [
            'name' => '电脑无法开机（修订）',
            'subject' => '[开机失败] 模板',
            'description' => '更新描述',
            'priority' => 'high',
        ])->assertRedirect(route('admin.ticket-templates.index'));

        $this->assertDatabaseHas('ticket_templates', ['id' => $template->id, 'priority' => 'high']);

        $this->delete(route('admin.ticket-templates.destroy', $template))
            ->assertRedirect(route('admin.ticket-templates.index'));

        $this->assertDatabaseMissing('ticket_templates', ['id' => $template->id]);
    }

    public function test_ticket_template_can_bind_category_and_product(): void
    {
        $category = Category::create(['name' => '硬件', 'slug' => 'hw', 'is_active' => true]);
        $product = Product::create(['name' => '工控主机', 'sku' => 'IPC-01', 'warranty_days' => 365, 'is_active' => true]);

        $this->actingAs($this->agentWithModules(['templates']))
            ->post(route('admin.ticket-templates.store'), [
                'name' => '硬件模板',
                'subject' => '硬件问题模板',
                'description' => '描述',
                'priority' => 'urgent',
                'category_id' => $category->id,
                'product_id' => $product->id,
            ])
            ->assertRedirect(route('admin.ticket-templates.index'));

        $this->assertDatabaseHas('ticket_templates', ['category_id' => $category->id, 'product_id' => $product->id]);
    }

    // -------------------------------------------------------------------------
    // 报表
    // -------------------------------------------------------------------------

    public function test_report_page_renders_for_agent(): void
    {
        $this->actingAs($this->agentWithModules(['reports']))
            ->get(route('admin.reports'))
            ->assertOk();
    }

    public function test_report_export_returns_csv(): void
    {
        Ticket::factory()->count(3)->create();

        $response = $this->actingAs($this->agentWithModules(['reports']))
            ->get(route('admin.reports.export', ['type' => 'tickets']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_report_requires_module_permission(): void
    {
        // 只授权 customers，不含 reports
        $this->actingAs($this->agentWithModules(['customers']))
            ->get(route('admin.reports'))
            ->assertForbidden();
    }
}
