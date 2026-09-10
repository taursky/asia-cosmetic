<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use LangTrait;

    protected $fillable = ['code', 'type', 'external_id', 'one_c_id', 'is_filterable', 'is_variant', 'is_searchable', 'sort_order'];

    protected function casts(): array
    {
        return ['is_filterable' => 'boolean', 'is_variant' => 'boolean', 'is_searchable' => 'boolean'];
    }

    public function values(): HasMany { return $this->hasMany(AttributeValue::class); }
}
