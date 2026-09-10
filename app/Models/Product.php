<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, LangTrait;

    protected $fillable = [
        'external_id', 'one_c_id', 'sku', 'source', 'source_url', 'is_active', 'is_visible',
        'allow_discounts', 'vat_rate', 'unit', 'weight', 'length', 'width', 'height',
        'video_url', 'rating', 'reviews_count', 'sync_hash', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'is_visible' => 'boolean', 'allow_discounts' => 'boolean',
            'vat_rate' => 'decimal:2', 'weight' => 'decimal:3', 'length' => 'decimal:3',
            'width' => 'decimal:3', 'height' => 'decimal:3', 'rating' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values')->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
