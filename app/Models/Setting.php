<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Setting：系统设置键值表（setting_key/value），运行时修改即时生效，通过 SettingService 读写。
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['setting_key', 'value'];

    protected $casts = ['value' => 'string'];
}
