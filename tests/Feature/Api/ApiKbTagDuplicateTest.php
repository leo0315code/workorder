<?php

namespace Tests\Feature\Api;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API 知识库 / 标签 / 重复工单识别 边界测试
 */
class ApiKbTagDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['setting_key' => 'work_hours_enabled', 'value' => '0']);
        Setting::create(['setting_key' => 'site_name', 'value' => '测试工单']);
    }

    private function makeUser(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role, 'password' => bcrypt('password')]);
    }

    // -------------------------------------------------------------------------
    // 知识库 API
    // -------------------------------------------------------------------------

    public function test_kb_categories_returns_published_counts(): void
    {
        $cat = KbCategory::create(['name' => '常见故障']);
        KbArticle::create(['title' => 'A', 'content' => 'x', 'kb_category_id' => $cat->id, 'is_published' => true]);
        KbArticle::create(['title' => 'B', 'content' => 'x', 'kb_category_id' => $cat->id, 'is_published' => false]);

        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/kb/categories')
            ->assertOk()
            ->assertJsonPath('items.0.name', '常见故障')
            ->assertJsonPath('items.0.article_count', 1); // 只统计已发布
    }

    public function test_kb_articles_only_published_and_filterable(): void
    {
        $cat = KbCategory::create(['name' => '售后政策']);
        KbArticle::create(['title' => '续保政策', 'content' => '内容', 'kb_category_id' => $cat->id, 'is_published' => true]);
        KbArticle::create(['title' => '草稿文章', 'content' => 'x', 'is_published' => false]);

        Sanctum::actingAs($this->makeUser());

        // 草稿不出现
        $this->getJson('/api/kb/articles')->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.title', '续保政策');
        // 关键词筛选（中文 query 必须 URL 编码，否则污染测试后续请求）
        $this->getJson('/api/kb/articles?q='.urlencode('续保'))->assertOk()->assertJsonCount(1, 'items');
        // 分类筛选
        $this->getJson('/api/kb/articles?category_id='.$cat->id)->assertOk()->assertJsonCount(1, 'items');
    }

    public function test_kb_article_detail_counts_views_and_hides_draft(): void
    {
        $draft = KbArticle::create(['title' => '草稿', 'content' => 'x', 'is_published' => false]);
        $pub = KbArticle::create(['title' => '已发布', 'content' => '# 标题', 'is_published' => true]);

        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/kb/articles/{$draft->id}")->assertNotFound();
        $this->getJson("/api/kb/articles/{$pub->id}")
            ->assertOk()
            ->assertJsonPath('article.title', '已发布')
            ->assertJsonPath('article.content', '# 标题');
        $this->assertSame(1, $pub->fresh()->views);
    }

    // -------------------------------------------------------------------------
    // 标签 API + 工单 tag 筛选/返回
    // -------------------------------------------------------------------------

    public function test_tags_list_and_ticket_filter(): void
    {
        $user = $this->makeUser();
        Tag::create(['name' => '紧急', 'color' => 'red']);
        Tag::create(['name' => '售后', 'color' => 'emerald']);
        $tagged = Ticket::factory()->create(['user_id' => $user->id]);
        $tagged->tags()->attach(Tag::where('name', '紧急')->first()->id);
        Ticket::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/tags')->assertOk()->assertJsonCount(2, 'items');

        $this->getJson('/api/tickets?status=all&tag_id='.Tag::where('name', '紧急')->first()->id)
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.tags.0.name', '紧急');
    }

    public function test_ticket_detail_returns_tags(): void
    {
        $user = $this->makeUser();
        $tag = Tag::create(['name' => '高优', 'color' => 'rose']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $ticket->tags()->attach($tag->id);

        Sanctum::actingAs($user);

        $this->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('ticket.tags.0.name', '高优');
    }

    // -------------------------------------------------------------------------
    // 重复工单识别
    // -------------------------------------------------------------------------

    public function test_api_create_returns_duplicate_hint(): void
    {
        $user = $this->makeUser();
        $existing = Ticket::factory()->create(['user_id' => $user->id, 'subject' => '电脑无法开机', 'status' => 'open']);

        Sanctum::actingAs($user);

        $this->postJson('/api/tickets', [
            'subject' => '电脑无法开机',
            'description' => '又出现了',
            'priority' => 'normal',
        ])->assertCreated()
            ->assertJsonPath('duplicate.ticket_id', $existing->id)
            ->assertJsonPath('duplicate.no', $existing->no);
    }

    public function test_api_create_no_duplicate_when_subject_differs(): void
    {
        $user = $this->makeUser();
        Ticket::factory()->create(['user_id' => $user->id, 'subject' => '旧问题', 'status' => 'open']);

        Sanctum::actingAs($user);

        $this->postJson('/api/tickets', [
            'subject' => '新问题',
            'description' => '描述',
            'priority' => 'normal',
        ])->assertCreated()->assertJsonPath('duplicate', null);
    }

    public function test_api_create_no_duplicate_when_resolved(): void
    {
        $user = $this->makeUser();
        Ticket::factory()->create(['user_id' => $user->id, 'subject' => '已解决的问题', 'status' => 'resolved']);

        Sanctum::actingAs($user);

        $this->postJson('/api/tickets', [
            'subject' => '已解决的问题',
            'description' => '重提',
            'priority' => 'normal',
        ])->assertCreated()->assertJsonPath('duplicate', null);
    }
}
