<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerRole extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'customer_role_user',
        )->withTimestamps();
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(ProductPriceType::class, 'product_price_type_id');
    }
}
