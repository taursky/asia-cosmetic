<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Option extends Model
{
    use SoftDeletes, LangTrait;

    protected $fillable = ['product_id', 'external_id', 'one_c_id', 'sku', 'barcode', 'is_active', 'stock', 'weight', 'length', 'width', 'height', 'sort_order', 'sync_hash', 'synced_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'stock' => 'decimal:3', 'weight' => 'decimal:3', 'length' => 'decimal:3', 'width' => 'decimal:3', 'height' => 'decimal:3', 'synced_at' => 'datetime'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function prices(): HasMany { return $this->hasMany(ProductPrice::class); }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function attributeValues(): BelongsToMany { return $this->belongsToMany(AttributeValue::class, 'option_attribute_values')->withPivot('sort_order')->orderByPivot('sort_order'); }
}
