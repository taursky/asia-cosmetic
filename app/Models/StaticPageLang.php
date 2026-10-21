<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaticPageLang extends Model
{
    protected $table = 'static_page_lang';

    protected $fillable = [
        'page_id', 'lang', 'title', 'slug', 'content',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(StaticPage::class, 'static_page_id');
    }
}
