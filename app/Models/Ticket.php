<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'no', 'user_id', 'customer_id', 'category_id', 'product_id',
        'subject', 'description', 'priority', 'status',
        'assignee_id', 'sla_due_at', 'last_reply_at', 'closed_at',
    ];

    protected $casts = [
        'sla_due_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // 各优先级默认 SLA 小时数
    public static array $slaHours = [
        self::PRIORITY_LOW => 72,
        self::PRIORITY_NORMAL => 48,
        self::PRIORITY_HIGH => 24,
        self::PRIORITY_URGENT => 8,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class)->with('user')->orderByDesc('created_at');
    }

    public function rating()
    {
        return $this->hasOne(TicketRating::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_RESOLVED]);
    }

    // 实时推送房间名
    public function channelName(): string
    {
        return 'ticket.'.$this->id;
    }

    public function isOverdue(): bool
    {
        return $this->sla_due_at
            && $this->sla_due_at->isPast()
            && ! in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }
}
