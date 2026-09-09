<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * TicketReply：工单回复。type=reply 为客户可见的对话消息，type=note 为仅内部可见的备注（isNote() 判断）。
 */
class TicketReply extends Model
{
    use HasFactory;

    public const TYPE_REPLY = 'reply'; // 客户可见

    public const TYPE_NOTE = 'note';   // 仅内部

    protected $fillable = ['ticket_id', 'user_id', 'content', 'type'];

    protected $casts = [];

    /**
     * 内容归一化：统一换行符并去除首尾空白/空行。
     * 否则 textarea 末尾的空行会随 whitespace-pre-wrap 渲染成超高气泡。
     */
    public function setContentAttribute($value): void
    {
        $this->attributes['content'] = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 该回复的附件（多态挂载，对话气泡内展示）
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isNote(): bool
    {
        return $this->type === self::TYPE_NOTE;
    }
}
