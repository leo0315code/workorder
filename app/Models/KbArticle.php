<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 知识库文章：客服沉淀常见问题/操作手册，可发布为客服端阅读；
 * is_published=false 为草稿（仅作者与管理员可见）
 */
class KbArticle extends Model
{
    use HasFactory;

    protected $fillable = ['kb_category_id', 'title', 'content', 'is_published', 'views', 'created_by'];

    protected $casts = [
        'is_published' => 'boolean',
        'views' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
