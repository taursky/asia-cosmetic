<?php

namespace App\Services\Counterparties;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DaDataService
{
    public function suggest(string $query, int $count = 10): array
    {
        return $this->post('suggest/party', [
            'query' => trim($query),
            'count' => min(max($count, 1), 20),
            'status' => ['ACTIVE'],
        ]);
    }

    public function findById(string $query): array
    {
        return $this->post('findById/party', [
            'query' => trim($query),
            'count' => 10,
            'branch_type' => 'MAIN',
        ]);
    }

    private function post(string $method, array $payload): array
    {
        $token = trim((string) config('counterparties.dadata.token'));

        if ($token === '') {
            throw new RuntimeException('DaData не настроена: отсутствует DADATA_TOKEN.');
        }

        try {
            $response = $this->client()->post(
                rtrim((string) config('counterparties.dadata.base_url'), '/') . '/' . ltrim($method, '/'),
                $payload,
            );
        } catch (ConnectionException $e) {
            throw new RuntimeException('DaData недоступна по сети.', previous: $e);
        }

        if ($response->status() === 403) {
            throw new RuntimeException('DaData отклонила запрос: ключ недоступен или исчерпан дневной лимит.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('DaData временно ограничила частоту запросов.');
        }

        if ($response->serverError()) {
            throw new RuntimeException('DaData временно недоступна. HTTP ' . $response->status() . '.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Ошибка DaData. HTTP ' . $response->status() . '.');
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('DaData вернула некорректный JSON.');
        }

        return $json;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken((string) config('counterparties.dadata.token'), 'Token')
            ->timeout((int) config('counterparties.dadata.timeout', 10));
    }
}
