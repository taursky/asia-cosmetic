<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValue extends Model
{
    use LangTrait;

    protected $fillable = ['attribute_id', 'code', 'external_id', 'one_c_id', 'numeric_value', 'boolean_value', 'sort_order'];

    protected function casts(): array { return ['numeric_value' => 'decimal:6', 'boolean_value' => 'boolean']; }

    public function attribute(): BelongsTo { return $this->belongsTo(Attribute::class); }

    public function products(): BelongsToMany { return $this->belongsToMany(Product::class, 'product_attribute_values'); }

    public function options(): BelongsToMany { return $this->belongsToMany(Option::class, 'option_attribute_values'); }
}
