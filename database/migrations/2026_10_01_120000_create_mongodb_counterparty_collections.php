<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection('mongodb')->create('counterparties', function (Blueprint $collection): void {
            $collection->unique('identity_key');
            $collection->index('inn');
            $collection->index('kpp');
            $collection->index('ogrn');
            $collection->index('hid');
            $collection->index('name_normalized');
            $collection->index('search_tokens');
            $collection->index('source');
            $collection->index('fetched_at');
            $collection->index(['inn' => 1, 'kpp' => 1]);
        });

        Schema::connection('mongodb')->create('counterparty_queries', function (Blueprint $collection): void {
            $collection->index('query_normalized');
            $collection->index('kind');
            $collection->index('source');
            $collection->index('ok');
            $collection->expire('created_at', max(1, (int) config('counterparties.query_log_ttl_days', 90)) * 86400);
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('counterparty_queries');
        Schema::connection('mongodb')->dropIfExists('counterparties');
    }
};
