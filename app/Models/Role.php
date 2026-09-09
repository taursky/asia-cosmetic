<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Получить всех пользователей с этой ролью
     */
    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_roles')
            ->withTimestamps();
    }

    /**
     * Проверка, активна ли роль
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Получить количество пользователей с этой ролью
     */
    public function getUsersCountAttribute(): int
    {
        return $this->adminUsers()->count();
    }
}
