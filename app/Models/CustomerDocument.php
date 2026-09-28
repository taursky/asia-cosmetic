<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available_from' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isAvailable(): bool
    {
        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
