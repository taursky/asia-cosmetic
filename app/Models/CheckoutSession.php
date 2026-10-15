<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutSession extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'status', 'customer_type', 'payment_method',
        'currency', 'subtotal', 'delivery_amount', 'discount_amount', 'total',
        'customer_data', 'delivery_data', 'pricing_snapshot', 'expires_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_data' => 'array',
            'delivery_data' => 'array',
            'pricing_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'delivery_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CheckoutSessionItem::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
