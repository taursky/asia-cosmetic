<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionValueLang extends Model
{
    protected $table = 'option_value_lang';
    protected $guarded = [];

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class);
    }
}
