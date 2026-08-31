<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category：工单分类。slug 由拼音化名称+随机后缀自动生成，仅内部使用，不要求用户维护。
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
