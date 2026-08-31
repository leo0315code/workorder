<?php

namespace Tests\Feature;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KbTest extends TestCase
{
    use RefreshDatabase;

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
    }

    private function article(array $attrs = []): KbArticle
    {
        return KbArticle::create(array_merge([
            'title' => '测试文章',
            'content' => '# 标题\n正文内容',
            'is_published' => true,
            'created_by' => auth()->id() ?? 1,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // 文章 CRUD
    // -------------------------------------------------------------------------

    public function test_agent_can_create_and_view_article(): void
    {
        $user = $this->agent();
        $this->actingAs($user);

        $this->post(route('admin.kb.store'), [
            'title' => '常见问题 FAQ',
            'content' => '## 如何重置密码',
            'is_published' => '1',
        ])->assertRedirect(route('admin.kb.index'));

        $this->assertDatabaseHas('kb_articles', ['title' => '常见问题 FAQ', 'created_by' => $user->id]);

        $this->get(route('admin.kb.index'))->assertSee('常见问题 FAQ');
    }

    public function test_agent_can_edit_own_article(): void
    {
        $user = $this->agent();
        $this->actingAs($user);
        $article = $this->article(['created_by' => $user->id]);

        $this->patch(route('admin.kb.update', $article), [
            'title' => '更新后的标题',
            'content' => '新内容',
        ])->assertRedirect(route('admin.kb.index'));

        $this->assertDatabaseHas('kb_articles', ['id' => $article->id, 'title' => '更新后的标题']);
    }

    public function test_agent_cannot_edit_others_article(): void
    {
        $other = $this->agent();
        $article = $this->article(['created_by' => $other->id]);

        $this->actingAs($this->agent())
            ->patch(route('admin.kb.update', $article), ['title' => '篡改', 'content' => 'x'])
            ->assertForbidden();
    }

    public function test_article_toggle_publish(): void
    {
        $user = $this->agent();
        $this->actingAs($user);
        $article = $this->article(['created_by' => $user->id, 'is_published' => true]);

        $this->patch(route('admin.kb.update', $article), [
            'title' => $article->title,
            'content' => $article->content,
            'is_published' => '0',
        ])->assertRedirect(route('admin.kb.index'));

        $this->assertFalse($article->fresh()->is_published);
    }

    // -------------------------------------------------------------------------
    // 阅读页
    // -------------------------------------------------------------------------

    public function test_published_article_is_readable_and_counts_views(): void
    {
        $user = $this->agent();
        $article = $this->article(['created_by' => $user->id, 'is_published' => true]);

        $this->actingAs($this->agent())->get(route('kb.show', $article))
            ->assertOk()
            ->assertSee($article->title);

        $this->assertSame(1, $article->fresh()->views);
    }

    public function test_draft_article_is_not_publicly_readable(): void
    {
        $user = $this->agent();
        $article = $this->article(['created_by' => $user->id, 'is_published' => false]);

        $this->actingAs($this->agent())->get(route('kb.show', $article))->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // 分类
    // -------------------------------------------------------------------------

    public function test_category_crud(): void
    {
        $this->actingAs($this->agent());

        $this->post(route('admin.kb.categories.store'), ['name' => '硬件故障'])->assertJsonPath('category.name', '硬件故障');

        $category = KbCategory::where('name', '硬件故障')->firstOrFail();

        $this->patch(route('admin.kb.categories.update', $category), ['name' => '硬件类'])
            ->assertJsonPath('category.name', '硬件类');

        $category->refresh();
        $this->delete(route('admin.kb.categories.destroy', $category))->assertJson(['message' => '分类已删除']);
        $this->assertDatabaseMissing('kb_categories', ['id' => $category->id]);
    }

    public function test_category_with_articles_cannot_be_deleted(): void
    {
        $this->actingAs($this->agent());
        $category = KbCategory::create(['name' => '有文章的分类']);
        $this->article(['kb_category_id' => $category->id]);

        $this->delete(route('admin.kb.categories.destroy', $category))->assertStatus(422);
        $this->assertDatabaseHas('kb_categories', ['id' => $category->id]);
    }

    // -------------------------------------------------------------------------
    // 权限
    // -------------------------------------------------------------------------

    public function test_customer_cannot_access_kb_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.kb.index'))
            ->assertForbidden();
    }

    public function test_agent_sees_only_own_drafts_in_list(): void
    {
        $me = $this->agent();
        $other = $this->agent();
        $this->article(['title' => '我的草稿', 'is_published' => false, 'created_by' => $me->id]);
        $this->article(['title' => '别人的草稿', 'is_published' => false, 'created_by' => $other->id]);

        $this->actingAs($me)->get(route('admin.kb.index'))
            ->assertSee('我的草稿')
            ->assertDontSee('别人的草稿');
    }
}
