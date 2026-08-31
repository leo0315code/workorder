<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentionTest extends TestCase
{
    use RefreshDatabase;

    private function agent(string $name = '王客服'): User
    {
        return User::factory()->create(['role' => 'agent', 'name' => $name, 'password' => bcrypt('password')]);
    }

    private function ticket(User $customer = null): Ticket
    {
        return Ticket::factory()->create(['user_id' => $customer?->id ?? 1]);
    }

    public function test_reply_mentions_colleague_notifies_them(): void
    {
        $me = $this->agent('李客服');
        $colleague = $this->agent('王客服');
        $customer = User::factory()->create(['role' => 'customer']);
        $ticket = $this->ticket($customer);

        $this->actingAs($me)->post(route('tickets.reply', $ticket), [
            'content' => '这个工单需要 @王客服 协助处理',
        ])->assertRedirect();

        $this->assertTrue(
            UserNotification::where('user_id', $colleague->id)->where('title', '有人 @ 了你')->exists()
        );
        // 自己不通知
        $this->assertFalse(
            UserNotification::where('user_id', $me->id)->where('title', '有人 @ 了你')->exists()
        );
    }

    public function test_note_mentions_colleague_notifies_them(): void
    {
        $me = $this->agent('李客服');
        $colleague = $this->agent('张主管');
        $customer = User::factory()->create(['role' => 'customer']);
        $ticket = $this->ticket($customer);

        $this->actingAs($me)->post(route('tickets.note', $ticket), [
            'content' => '内部备注：@张主管 请复核',
        ])->assertRedirect();

        $this->assertTrue(
            UserNotification::where('user_id', $colleague->id)->where('title', '有人 @ 了你')->exists()
        );
    }

    public function test_mention_only_matches_staff_names(): void
    {
        $me = $this->agent('李客服');
        // 客户也叫「王客服」不应被提及通知（只匹配客服/管理员）
        User::factory()->create(['role' => 'customer', 'name' => '王客服']);
        $ticket = $this->ticket();

        $this->actingAs($me)->post(route('tickets.reply', $ticket), [
            'content' => '请 @王客服 处理',
        ])->assertRedirect();

        // 没有任何「有人 @ 了你」通知（该名字只有客户，客服名单里无此名；
        // 但客服回复本身会通知客户「有新回复」，故只断言无 @ 通知）
        $this->assertFalse(
            UserNotification::where('title', '有人 @ 了你')->exists()
        );
    }

    public function test_plain_text_without_at_does_not_notify(): void
    {
        $me = $this->agent('李客服');
        $colleague = $this->agent('王客服');
        $customer = User::factory()->create(['role' => 'customer']);
        $ticket = $this->ticket($customer);

        $this->actingAs($me)->post(route('tickets.reply', $ticket), [
            'content' => '普通回复，没有提及任何人',
        ])->assertRedirect();

        $this->assertFalse(
            UserNotification::where('user_id', $colleague->id)->where('title', '有人 @ 了你')->exists()
        );
    }
}
