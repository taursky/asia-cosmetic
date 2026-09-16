<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'external_id',
        'one_c_id',
        'code',
        'name',
        'is_active',
        'sort_order',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductVariantStock::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_stocks'
        )
            ->withPivot(['quantity', 'reserved', 'available', 'synced_at'])
            ->withTimestamps();
    }
}
