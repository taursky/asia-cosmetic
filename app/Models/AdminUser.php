<?php
// app/Models/AdminUser.php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticate;
//use Spatie\Permission\Traits\HasRoles;
//use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminUser extends Authenticate implements FilamentUser
{
//    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'admin_users';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'remember_token',
    ];

    protected $guard_name = 'admin';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';//&& $this->hasRole('admin', 'admin')
    }

    /**
     * Получить все роли пользователя
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_user_roles')
            ->withTimestamps();
    }

    /**
     * Проверка, есть ли у пользователя определенная роль
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Проверка, есть ли у пользователя хотя бы одна из ролей
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()
            ->whereIn('slug', $roleSlugs)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Проверка, есть ли у пользователя все роли
     */
    public function hasAllRoles(array $roleSlugs): bool
    {
        if ($roleSlugs === []) {
            return true;
        }

        $count = $this->roles()
            ->whereIn('slug', $roleSlugs)
            ->where('is_active', true)
            ->distinct()
            ->count('roles.id');

        return $count === count(array_unique($roleSlugs));
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->roles()->where('is_active', true)->exists();
    }

    /**
     * Получить список названий ролей
     */
    public function getRoleNamesAttribute(): string
    {
        return $this->roles->pluck('name')->implode(', ');
    }

    /**
     * Получить активные роли пользователя
     */
    public function getActiveRolesAttribute()
    {
        return $this->roles()->where('is_active', true)->get();
    }
}
