<?php

namespace App\Services\Fns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiFnsService
{
    public function suggest(string $query): array
    {
        $payload = $this->get('search', ['q' => trim($query)]);

        return collect($payload['items'] ?? [])
            ->map(fn (array $item) => $this->normalizeSuggestion($item))
            ->filter()
            ->values()
            ->all();
    }

    public function company(string $innOrOgrn): array
    {
        $payload = $this->get('egr', ['req' => trim($innOrOgrn)]);
        $item = $payload['items'][0] ?? null;

        if (! is_array($item)) {
            throw new RuntimeException('Контрагент не найден в API-ФНС.');
        }

        return [
            'raw' => $item,
            'profile' => $this->mapProfile($item),
        ];
    }

    public function check(string $innOrOgrn): array
    {
        return $this->get('check', ['req' => trim($innOrOgrn)]);
    }

    private function get(string $method, array $params): array
    {
        $key = trim((string) config('api_fns.key'));

        if ($key === '') {
            throw new RuntimeException('API-ФНС не настроен.');
        }

        try {
            $response = $this->client()->get(
                rtrim((string) config('api_fns.base_url'), '/') . '/' . ltrim($method, '/'),
                [...$params, 'key' => $key],
            );
        } catch (ConnectionException $e) {
            throw new RuntimeException('API-ФНС недоступен по сети.', previous: $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('API-ФНС недоступен. HTTP ' . $response->status() . ': ' . mb_substr(trim($response->body()), 0, 250));
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('API-ФНС вернул некорректный JSON.');
        }

        return $json;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout((int) config('api_fns.timeout', 10));
    }

    private function normalizeSuggestion(array $item): ?array
    {
        [$type, $data] = $this->entity($item);
        if (! $data) return null;

        $isIp = $type === 'ИП';
        return [
            'type' => $isIp ? 'INDIVIDUAL' : 'LEGAL',
            'inn' => (string) ($data['ИНН'] ?? $data['ИННФЛ'] ?? ''),
            'kpp' => $isIp ? null : ($data['КПП'] ?? null),
            'ogrn' => (string) ($data['ОГРН'] ?? $data['ОГРНИП'] ?? ''),
            'name' => $isIp ? ($data['ФИОПолн'] ?? null) : ($data['НаимСокрЮЛ'] ?? $data['НаимПолнЮЛ'] ?? null),
            'address' => $this->stringAddress($data['АдресПолн'] ?? null),
            'status' => $data['Статус'] ?? null,
        ];
    }

    public function mapProfile(array $item): array
    {
        [$type, $data] = $this->entity($item);
        if (! $data) throw new RuntimeException('Не удалось определить тип контрагента.');

        if ($type === 'ИП') {
            $fio = $data['ФИОПолн'] ?? null;
            return [
                'legal_type' => 'individual_entrepreneur',
                'company_name' => $fio ? 'ИП ' . $fio : null,
                'full_company_name' => $fio ? 'Индивидуальный предприниматель ' . $fio : null,
                'inn' => $data['ИНН'] ?? $data['ИННФЛ'] ?? null,
                'kpp' => null,
                'ogrn' => null,
                'ogrnip' => $data['ОГРНИП'] ?? $data['ОГРН'] ?? null,
                'legal_address' => $this->stringAddress($data['АдресПолн'] ?? null),
                'director_name' => $fio,
                'director_position' => 'Индивидуальный предприниматель',
                'fns_status' => $data['Статус'] ?? null,
            ];
        }

        $director = is_array($data['Руководитель'] ?? null) ? $data['Руководитель'] : [];
        return [
            'legal_type' => 'legal_entity',
            'company_name' => $data['НаимСокрЮЛ'] ?? $data['НаимПолнЮЛ'] ?? null,
            'full_company_name' => $data['НаимПолнЮЛ'] ?? $data['НаимСокрЮЛ'] ?? null,
            'inn' => $data['ИНН'] ?? null,
            'kpp' => $data['КПП'] ?? null,
            'ogrn' => $data['ОГРН'] ?? null,
            'ogrnip' => null,
            'legal_address' => $this->stringAddress($data['АдресПолн'] ?? null),
            'director_name' => $director['ФИОПолн'] ?? null,
            'director_position' => $director['Должн'] ?? null,
            'fns_status' => $data['Статус'] ?? null,
        ];
    }

    private function entity(array $item): array
    {
        foreach (['ЮЛ', 'ИП', 'НР', 'ИН'] as $key) {
            if (isset($item[$key]) && is_array($item[$key])) return [$key, $item[$key]];
        }
        return [null, null];
    }

    private function stringAddress(mixed $value): ?string
    {
        if (is_string($value)) return trim($value) ?: null;
        if (! is_array($value)) return null;
        foreach (['АдресПолн', 'Текст', 'Представление'] as $key) {
            if (! empty($value[$key]) && is_string($value[$key])) return trim($value[$key]);
        }
        return null;
    }
}
