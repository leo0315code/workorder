<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
