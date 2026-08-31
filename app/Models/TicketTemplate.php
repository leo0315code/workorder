<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketTemplate：工单模板：新建工单时可选用模板预填 subject/description，支持占位符（如 {客户姓名}）由前端替换。
 */
class TicketTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subject', 'description', 'category_id', 'product_id', 'priority', 'is_active', 'sort'];

    protected $casts = ['is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
