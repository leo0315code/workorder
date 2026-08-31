<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer：客户档案：company/contact_name 等基本资料 + product_id 关联产品，registered_at/after_sales_expired_at 用于保修追踪；expired/expiringSoon 访问器计算售后状态。
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company', 'contact_name', 'phone', 'email', 'address',
        'product_id', 'registered_at', 'after_sales_expired_at', 'remark',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'after_sales_expired_at' => 'datetime',
    ];

    // 售后是否已过期
    public function getExpiredAttribute(): bool
    {
        return $this->after_sales_expired_at
            && $this->after_sales_expired_at->isPast();
    }

    // 是否临期（7 天内）
    public function getExpiringSoonAttribute(): bool
    {
        return $this->after_sales_expired_at
            && ! $this->expired
            && $this->after_sales_expired_at->diffInDays(now()) <= 7;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
