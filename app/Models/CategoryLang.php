<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryLang extends Model
{
    use HasFactory;

    protected $table = 'category_lang';

    protected $fillable = ['category_id', 'lang', 'name', 'slug', 'description', 'seo_title', 'seo_description'];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
