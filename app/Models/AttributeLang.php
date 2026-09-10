<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeLang extends Model
{
    protected $table = 'attribute_lang';

    protected $fillable = ['attribute_id', 'lang', 'name', 'unit', 'description'];
    public function attribute(): BelongsTo { return $this->belongsTo(Attribute::class); }
}
