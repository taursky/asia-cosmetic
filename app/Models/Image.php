<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    public $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
