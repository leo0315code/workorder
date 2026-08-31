<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 工单自定义字段定义（管理员在「工单字段」维护）
 * type: text | number | select | date；select 的选项存 options JSON
 */
class TicketFieldDef extends Model
{
    use HasFactory;

    public const TYPES = ['text' => '文本', 'number' => '数字', 'select' => '下拉选择', 'date' => '日期'];

    protected $fillable = ['label', 'key', 'type', 'options', 'is_required', 'is_active', 'sort'];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];
}
