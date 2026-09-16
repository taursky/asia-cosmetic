<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantStock extends Model
{
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved',
        'available',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'reserved' => 'decimal:3',
            'available' => 'decimal:3',
            'synced_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
