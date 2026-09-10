<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLang extends Model
{
//    use HasFactory;
    protected $table = 'product_lang';

    protected $fillable = ['product_id', 'lang', 'name', 'slug', 'short_description', 'description', 'seo_title', 'seo_keywords', 'seo_description'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
