<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_AGENT = 'agent';
    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_CUSTOMER => '客户',
        self::ROLE_AGENT => '客服',
        self::ROLE_ADMIN => '管理员',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'role',
        'password',
        'wechat_openid',
        'wechat_unionid',
        'wechat_nickname',
        'wechat_avatar',
        'manual_offline',
        'permissions',
        'agent_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'manual_offline' => 'boolean',
        'permissions' => 'array',
    ];

    /**
     * 客服可授权的后台模块（业务菜单 + 高级操作）
     */
    public const AGENT_MODULES = [
        'customers' => '客户档案',
        'products' => '产品管理',
        'categories' => '分类管理',
        'quick-replies' => '快捷回复',
        'templates' => '工单模板',
        'reports' => '数据报表',
        'export' => '工单导出',
        'batch' => '批量操作',
    ];

    /**
     * 未配置权限（null）时的默认全量模块（兼容存量客服）
     */
    public function defaultPermissions(): array
    {
        return array_keys(self::AGENT_MODULES);
    }

    /**
     * 是否可访问指定模块（菜单显示 + 后端守卫共用）
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // 优先：分配的角色（模块集合）
        if ($this->agentRole && $this->agentRole->is_active) {
            return in_array($module, $this->agentRole->modules ?? [], true);
        }

        // 兼容：旧的 per-user 权限字段
        if (is_array($this->permissions)) {
            return in_array($module, $this->permissions, true);
        }

        return in_array($module, $this->defaultPermissions(), true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return in_array($this->role, ['agent', 'admin']);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * 是否被管理员手动置为离线（不参与自动分配）
     */
    public function isManuallyOffline(): bool
    {
        return (bool) $this->manual_offline;
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function agentRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AgentRole::class, 'agent_role_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class)->orderByDesc('created_at');
    }

    public function unreadNotifications()
    {
        return $this->hasMany(UserNotification::class)->unread();
    }
}
