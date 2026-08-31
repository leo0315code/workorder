<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product：产品。warranty_days（保修天数）用于客户档案的售后到期自动计算（CustomerController::validated）。
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'sku', 'description', 'image', 'warranty_days', 'is_active',
    ];

    protected $casts = [
        'warranty_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
