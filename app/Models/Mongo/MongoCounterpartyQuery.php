<?php

namespace App\Models\Mongo;

use MongoDB\Laravel\Eloquent\Model;

class MongoCounterpartyQuery extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'counterparty_queries';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
