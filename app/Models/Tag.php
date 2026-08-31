<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 工单标签（多对多关联 Ticket，经 ticket_tag 中间表）
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color'];

    /** 可用徽标配色（与 ticket-status/priority 一致的口径） */
    public const COLORS = ['indigo', 'sky', 'emerald', 'amber', 'rose', 'violet', 'cyan', 'orange'];
}
