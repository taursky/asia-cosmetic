<?php

namespace App\Services\Counterparties;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
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

    public function findBankByBik(string $bik): array
    {
        $bik = preg_replace('/\\D+/', '', $bik) ?: '';

        if (! preg_match('/^\\d{9}$/', $bik)) {
            throw new RuntimeException('БИК должен состоять из 9 цифр.');
        }

        return Cache::remember(
            'dadata:bank:bik:' . $bik,
            now()->addDay(),
            function () use ($bik): array {
                $payload = $this->post('findById/bank', [
                    'query' => $bik,
                    'count' => 1,
                ]);

                $item = $payload['suggestions'][0] ?? null;
                $data = is_array($item) ? ($item['data'] ?? null) : null;

                if (! is_array($item) || ! is_array($data)) {
                    throw new RuntimeException('Банк с таким БИК не найден.');
                }

                return [
                    'name' => $data['name']['payment']
                        ?? $data['name']['short']
                        ?? $item['value']
                        ?? null,
                    'bic' => $data['bic'] ?? $bik,
                    'correspondent_account' => $data['correspondent_account'] ?? null,
                    'payment_city' => $data['payment_city'] ?? null,
                    'address' => $data['address']['unrestricted_value']
                        ?? $data['address']['value']
                        ?? null,
                    'status' => $data['state']['status'] ?? null,
                ];
            },
        );
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
