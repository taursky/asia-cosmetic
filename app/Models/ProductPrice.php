<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'old_amount' => 'decimal:2',
            'min_quantity' => 'decimal:3',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function priceType(): BelongsTo
    {
        return $this->belongsTo(ProductPriceType::class, 'product_price_type_id');
    }
}
