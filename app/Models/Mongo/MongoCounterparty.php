<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class MongoCounterparty extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'counterparties';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'search_tokens' => 'array',
            'provider_meta' => 'array',
            'fetched_at' => 'datetime',
            'actuality_at' => 'datetime',
        ];
    }
}
