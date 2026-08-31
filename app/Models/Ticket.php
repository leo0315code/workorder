<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Ticket：工单（核心模型）：STATUS_* 与 PRIORITY_* 常量贯穿全系统；sla_due_at 为 SLA 到期时间（每日巡检按此判超时）；含用户/负责人/分类/产品/回复/附件/评分等关联。
 */
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

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag')->withTimestamps();
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(TicketFieldValue::class);
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

    /**
     * SLA 剩余时间文案：如「剩余 23h」/「超时 5h」；已解决或无时限返回 null
     */
    public function slaLabel(): ?string
    {
        if (! $this->sla_due_at || in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return null;
        }

        $diff = $this->sla_due_at->diffInMinutes(now(), false);

        if ($diff < 0) {
            return '超时 '.self::formatDuration(abs($diff));
        }

        return '剩余 '.self::formatDuration($diff);
    }

    public function isSlaWarning(): bool
    {
        return $this->sla_due_at && ! $this->isOverdue()
            && $this->sla_due_at->isBefore(now()->addHours(6))
            && ! in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    protected static function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }
        if ($minutes < 24 * 60) {
            return round($minutes / 60).'h';
        }

        return round($minutes / 60 / 24).'d';
    }
}
