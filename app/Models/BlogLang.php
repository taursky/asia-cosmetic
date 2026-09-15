<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogLang extends Model
{
    protected $table = 'blog_lang';

    protected $guarded = [];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
