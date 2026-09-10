<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use LangTrait;

    protected $fillable = ['parent_id', 'external_id', 'one_c_id', 'is_active', 'sort_order'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }

    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }

    public function products(): BelongsToMany { return $this->belongsToMany(Product::class)->withPivot('sort_order'); }
}
