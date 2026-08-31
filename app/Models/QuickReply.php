<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * QuickReply：快捷回复话术：客服在工单对话页一键插入的常用回复。
 */
class QuickReply extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
