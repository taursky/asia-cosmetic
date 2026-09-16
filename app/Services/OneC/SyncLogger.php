<?php

namespace App\Services\OneC;

use App\Models\SyncLog;
use Throwable;

class SyncLogger
{
    public function run(string $entityType, string $direction, ?string $exchangeId, array $payload, callable $callback): mixed
    {
        $log = SyncLog::create([
            'direction' => $direction,
            'entity_type' => $entityType,
            'exchange_id' => $exchangeId,
            'status' => 'processing',
            'request_payload' => $payload,
            'started_at' => now(),
        ]);

        try {
            $result = $callback();
            $response = is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : $result;

            $log->update([
                'status' => 'success',
                'response_payload' => is_array($response) ? $response : ['result' => $response],
                'finished_at' => now(),
            ]);

            return $result;
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }
}
