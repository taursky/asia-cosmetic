<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/*
 * PATCH ДЛЯ СУЩЕСТВУЮЩЕГО App\Models\User
 *
 * Добавьте эти поля в Fillable / #[Fillable]:
 *
 * 'name',
 * 'email',
 * 'phone',
 * 'password',
 * 'is_active',
 *
 * В casts():
 *
 * 'email_verified_at' => 'datetime',
 * 'phone_verified_at' => 'datetime',
 * 'last_login_at' => 'datetime',
 * 'is_active' => 'boolean',
 * 'password' => 'hashed',
 *
 * И добавьте relationship:
 *
 * public function customerRoles(): BelongsToMany
 * {
 *     return $this->belongsToMany(
 *         CustomerRole::class,
 *         'customer_role_user'
 *     );
 * }
 */
final class UserPatchReference
{
    private function __construct()
    {
    }
}
