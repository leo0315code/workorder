<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SmsCode：短信验证码记录。scopeValid() 校验「未使用 + 未过期」；演示模式允许万能码 123456（可在系统设置关闭）。
 */
class SmsCode extends Model
{
    use HasFactory;

    protected $fillable = ['phone', 'code', 'expires_at', 'used_at', 'ip'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function scopeValid($query, string $phone, string $code)
    {
        return $query->where('phone', $phone)
            ->where('code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }
}
