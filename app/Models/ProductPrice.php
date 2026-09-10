<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'option_id', 'product_price_type_id', 'amount', 'old_amount', 'currency', 'min_quantity', 'external_id', 'one_c_id', 'valid_from', 'valid_until', 'synced_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'old_amount' => 'decimal:2', 'min_quantity' => 'decimal:3', 'valid_from' => 'datetime', 'valid_until' => 'datetime', 'synced_at' => 'datetime'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function option(): BelongsTo { return $this->belongsTo(Option::class); }
    public function type(): BelongsTo { return $this->belongsTo(ProductPriceType::class, 'product_price_type_id'); }
}
