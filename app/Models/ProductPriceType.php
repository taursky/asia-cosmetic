<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPriceType extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'external_id', 'one_c_id', 'sort_order', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function prices(): HasMany { return $this->hasMany(ProductPrice::class); }
}
