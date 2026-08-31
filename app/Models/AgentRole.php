<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AgentRole：客服角色模板（RBAC）：把一组模块权限打包进 modules 数组，分配给客服后由 User::canAccessModule() 与 module: 中间件生效。name 为内部标识（小写字母/数字/下划线），label 为显示名。
 */
class AgentRole extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label', 'description', 'modules', 'sort', 'is_active'];

    protected $casts = ['modules' => 'array', 'is_active' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
