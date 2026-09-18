<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function customerRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerRole::class,
            'customer_role_user',
        )->withTimestamps();
    }

    public function hasCustomerRole(string $code): bool
    {
        return $this->customerRoles()
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();
    }

    public function hasAnyCustomerRole(array $codes): bool
    {
        return $this->customerRoles()
            ->whereIn('code', $codes)
            ->where('is_active', true)
            ->exists();
    }

    public function customerRole(): BelongsTo
    {
        return $this->belongsTo(CustomerRole::class);
    }

    public function customerProfile(): HasOne { return $this->hasOne(CustomerProfile::class); }
    public function customerRoleHistory(): HasMany { return $this->hasMany(CustomerRoleHistory::class); }
    public function customerDocuments(): HasMany { return $this->hasMany(CustomerDocument::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
}
