<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 工单自定义字段值（ticket_id + field_def_id 唯一）
 */
class TicketFieldValue extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'field_def_id', 'value'];

    public function fieldDef(): BelongsTo
    {
        return $this->belongsTo(TicketFieldDef::class, 'field_def_id');
    }
}
