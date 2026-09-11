<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionLang extends Model
{
    protected $table = 'option_lang';
    protected $guarded = [];

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
