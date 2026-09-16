<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncState extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_success_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
