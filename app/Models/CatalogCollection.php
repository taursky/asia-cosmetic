<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogCollection extends Model
{
    protected $fillable = [
        'slug', 'title', 'description', 'seo_title', 'seo_description',
        'filter', 'sort', 'category_id', 'days', 'is_active', 'show_in_menu',
        'show_on_home', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean', 'show_in_menu' => 'boolean',
            'show_on_home' => 'boolean', 'days' => 'integer', 'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_collection_product');//->withTimestamps();
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('catalog.collection', ['slug' => $this->slug]);
    }
}
