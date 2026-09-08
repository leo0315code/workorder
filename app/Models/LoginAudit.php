<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LoginAudit：登录审计（谁在何时从何 IP 登录/尝试登录，成功或失败）
 */
class LoginAudit extends Model
{
    protected $fillable = [
        'user_id', 'email', 'channel', 'ip', 'user_agent', 'success', 'reason', 'login_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'login_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
