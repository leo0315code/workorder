<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 知识库分类（KB 文章分组）
 */
class KbCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sort', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'kb_category_id');
    }
}
