<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionValue extends Model
{
    use LangTrait;

    protected $fillable = [
        'option_id', 'code', 'external_id', 'one_c_id', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option_values')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_option_values')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
