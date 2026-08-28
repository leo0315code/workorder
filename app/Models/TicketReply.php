<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    use HasFactory;

    public const TYPE_REPLY = 'reply'; // 客户可见
    public const TYPE_NOTE = 'note';   // 仅内部

    protected $fillable = ['ticket_id', 'user_id', 'content', 'type'];

    protected $casts = [];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isNote(): bool
    {
        return $this->type === self::TYPE_NOTE;
    }
}
