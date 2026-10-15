<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutSessionItem extends Model
{
    protected $fillable = [
        'checkout_session_id', 'product_id', 'product_variant_id',
        'product_one_c_id', 'variant_one_c_id', 'sku', 'name',
        'quantity', 'price', 'total', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
