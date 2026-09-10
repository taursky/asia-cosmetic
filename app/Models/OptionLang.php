<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionLang extends Model
{
    use HasFactory;

    protected $table = 'option_lang';

    protected $fillable = ['option_id', 'lang', 'name', 'value', 'description'];

    public function option(): BelongsTo { return $this->belongsTo(Option::class); }
}
