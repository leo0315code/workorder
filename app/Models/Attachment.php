<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Attachment：附件（多态挂载：attachable_type/attachable_id 指向工单等）。path 相对私有盘 local（storage/app/private），下载必须走 TicketController::downloadAttachment() 鉴权，严禁存 public 盘被 /storage 直连。
 */
class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type', 'attachable_id', 'user_id',
        'original_name', 'path', 'mime_type', 'size',
    ];

    protected $casts = ['size' => 'integer'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
