<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Blog extends Model
{
    use LangTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'views_count' => 'integer',
            'product_ids' => 'array',
        ];
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function scopeActive($query)
    {
        return $query->where('blogs.active', true);
    }
}
