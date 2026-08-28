<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    use HasFactory;

    protected $fillable = ['phone', 'code', 'expires_at', 'used_at', 'ip'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function scopeValid($query, string $phone, string $code)
    {
        return $query->where('phone', $phone)
            ->where('code', $code)
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }
}
