<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
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

    private function ticket(User $user = null): Ticket
    {
        return Ticket::factory()->create(['user_id' => $user?->id ?? 1]);
    }

    public function test_agent_can_sync_existing_tags(): void
    {
        $agent = $this->agent();
        $tag = Tag::create(['name' => '紧急']);
        $ticket = $this->ticket();

        $this->actingAs($agent)
            ->post(route('tickets.tags', $ticket), ['tag_ids' => [$tag->id]])
            ->assertRedirect();

        $this->assertTrue($ticket->tags->contains('id', $tag->id));
    }

    public function test_agent_can_create_new_tag_on_the_fly(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticket();

        $this->actingAs($agent)
            ->post(route('tickets.tags', $ticket), ['tags' => ['硬件故障']])
            ->assertRedirect();

        $tag = Tag::where('name', '硬件故障')->firstOrFail();
        $this->assertTrue($ticket->fresh()->tags->contains('id', $tag->id));
    }

    public function test_sync_is_full_replace(): void
    {
        $agent = $this->agent();
        $a = Tag::create(['name' => 'A']);
        $b = Tag::create(['name' => 'B']);
        $ticket = $this->ticket();
        $ticket->tags()->attach([$a->id, $b->id]);

        $this->actingAs($agent)
            ->post(route('tickets.tags', $ticket), ['tag_ids' => [$a->id]])
            ->assertRedirect();

        $this->assertCount(1, $ticket->fresh()->tags);
        $this->assertTrue($ticket->fresh()->tags->contains('id', $a->id));
    }

    public function test_customer_cannot_manage_tags(): void
    {
        $customer = $this->customer();
        $ticket = $this->ticket($customer);

        $this->actingAs($customer)
            ->post(route('tickets.tags', $ticket), ['tags' => ['x']])
            ->assertForbidden();
    }

    public function test_list_filters_by_tag(): void
    {
        $agent = $this->agent();
        $tag = Tag::create(['name' => '高优']);
        $with = $this->ticket();
        $with->tags()->attach($tag->id);
        $without = $this->ticket();

        $this->actingAs($agent)
            ->get(route('tickets.index', ['tag' => $tag->id]))
            ->assertOk()
            ->assertSee($with->subject)
            ->assertDontSee($without->subject);
    }

    public function test_tags_visible_on_ticket_detail(): void
    {
        $agent = $this->agent();
        $tag = Tag::create(['name' => '售后']);
        $ticket = $this->ticket();
        $ticket->tags()->attach($tag->id);

        $this->actingAs($agent)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('售后');
    }
}
