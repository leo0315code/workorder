<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketLog extends Model
{
    use HasFactory;

    public const DESCRIPTIONS = [
        'created' => '创建了工单',
        'replied' => '回复了工单',
        'noted' => '添加了内部备注',
        'reopened' => '重新打开了工单',
        'change' => '变更了',
        'closed' => '关闭了工单',
    ];

    protected $fillable = [
        'ticket_id', 'user_id', 'action', 'field', 'old_value', 'new_value', 'note',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
