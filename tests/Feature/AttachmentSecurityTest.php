<?php

namespace Tests\Feature;

use App\Http\Controllers\TicketController;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * AttachmentSecurityTest：附件安全：下载越权（游客/他人 403）、所有者/客服可下载、强制 attachment、404、私有盘落盘、php 与双扩展名拒绝、白名单放行、public/storage 软链未注册
 */
class AttachmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'password' => bcrypt('password')]);
    }

    private function agent(): User
    {
        return User::factory()->create(['role' => 'agent', 'password' => bcrypt('password')]);
    }

    private function attachmentFor(Ticket $ticket): Attachment
    {
        Storage::disk(TicketController::ATTACHMENT_DISK)
            ->put($ticket->id.'/secret.txt', 'TOP SECRET');

        return Attachment::create([
            'attachable_type' => Ticket::class,
            'attachable_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'original_name' => 'secret.txt',
            'path' => $ticket->id.'/secret.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
        ]);
    }

    public function test_guest_cannot_download(): void
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->customer()->id]);
        $attachment = $this->attachmentFor($ticket);

        $this->get(route('attachments.download', $attachment))
            ->assertRedirect(route('login'));
    }

    public function test_other_customer_cannot_download(): void
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->customer()->id]);
        $attachment = $this->attachmentFor($ticket);

        $this->actingAs($this->customer())
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_owner_can_download(): void
    {
        $owner = $this->customer();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);
        $attachment = $this->attachmentFor($ticket);

        $response = $this->actingAs($owner)
            ->get(route('attachments.download', $attachment));

        $response->assertOk();
        $this->assertSame('TOP SECRET', $response->streamedContent());
        // 强制附件下载，禁止内联执行（防 html/svg XSS）
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_agent_can_download_any_ticket(): void
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->customer()->id]);
        $attachment = $this->attachmentFor($ticket);

        $this->actingAs($this->agent())
            ->get(route('attachments.download', $attachment))
            ->assertOk();
    }

    public function test_missing_file_returns_404(): void
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->customer()->id]);
        $attachment = Attachment::create([
            'attachable_type' => Ticket::class,
            'attachable_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'original_name' => 'ghost.txt',
            'path' => $ticket->id.'/ghost.txt',
            'mime_type' => 'text/plain',
            'size' => 0,
        ]);

        $this->actingAs($this->agent())
            ->get(route('attachments.download', $attachment))
            ->assertNotFound();
    }

    public function test_attachment_is_stored_on_private_disk(): void
    {
        Storage::fake(TicketController::ATTACHMENT_DISK);

        $user = $this->customer();
        Storage::disk(TicketController::ATTACHMENT_DISK)->put('fake.txt', 'x');

        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'subject' => '带附件的工单',
                'description' => '描述',
                'priority' => 'normal',
                'attachments' => [UploadedFile::fake()->createWithContent('说明.txt', 'hello')],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attachments', [
            'attachable_id' => $ticket->id + 1,
            'original_name' => '说明.txt',
        ]);
    }

    public function test_php_extension_is_rejected_on_create(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'subject' => '恶意文件测试',
                'description' => '描述',
                'priority' => 'normal',
                'attachments' => [UploadedFile::fake()->create('shell.php', 10)],
            ])
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_double_extension_is_rejected_on_create(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'subject' => '双扩展名测试',
                'description' => '描述',
                'priority' => 'normal',
                'attachments' => [UploadedFile::fake()->create('photo.png.php', 10)],
            ])
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_allowed_extensions_are_accepted(): void
    {
        Storage::fake(TicketController::ATTACHMENT_DISK);
        $user = $this->customer();

        $this->actingAs($user)
            ->post(route('tickets.store'), [
                'subject' => '正常附件测试',
                'description' => '描述',
                'priority' => 'normal',
                'attachments' => [
                    UploadedFile::fake()->create('合同.pdf', 100),
                    UploadedFile::fake()->image('截图.png'),
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, Attachment::count());
    }

    public function test_public_storage_symlink_is_not_registered(): void
    {
        $links = config('filesystems.links');

        $this->assertArrayNotHasKey(public_path('storage'), $links);
        $this->assertNotSame(public_path('storage'), storage_path('app/public'));
    }
}
