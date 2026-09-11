<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    use LangTrait;

    protected $fillable = [
        'code', 'external_id', 'one_c_id', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->orderBy('sort_order');
    }
}
