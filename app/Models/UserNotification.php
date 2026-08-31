<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserNotification：站内通知（表名 user_notifications）。link 为应用内相对路径（NotificationService::normalizeLink 处理，兼容任意域名）；scopeUnread() 过滤未读。
 */
class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $fillable = ['user_id', 'title', 'body', 'link', 'is_read'];

    protected $casts = ['is_read' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
