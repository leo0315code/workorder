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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'manual_offline' => 'boolean',
    ];

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
