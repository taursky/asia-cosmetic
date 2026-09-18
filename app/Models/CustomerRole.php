<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerRole extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_auto' => 'boolean',
            'level' => 'integer',
            'order_threshold_amount' => 'decimal:2',
            'qualification_amount' => 'decimal:2',
            'qualification_period_days' => 'integer',
            'validity_days' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'customer_role_user',
        )->withTimestamps();
    }

    public function currentUsers(): HasMany
    {
        return $this->hasMany(User::class, 'customer_role_id');
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(ProductPriceType::class, 'product_price_type_id');
    }
}
