<?php

namespace App\Services\Counterparties;

use App\Models\Mongo\MongoCounterparty;
use App\Models\Mongo\MongoCounterpartyQuery;
use App\Services\Fns\ApiFnsService;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CounterpartyDirectoryService
{
    public function __construct(
        private readonly DaDataService $dadata,
        private readonly ApiFnsService $fns,
    ) {}

    public function suggest(string $query, ?int $userId = null): array
    {
        $started = microtime(true);
        $query = trim($query);
        $normalized = $this->normalize($query);

        if ($normalized === '') return [];

        $local = $this->localSuggest($query);

        if ($local) {
            $this->logQuery($query, 'suggest', 'mongodb', true, count($local), $started, $userId);
            return $local;
        }

        if ($this->isExactIdentifier($query)) {
            try {
                $result = $this->lookup($query, $userId, forceRemote: true);
                return $result ? [$this->suggestionFromDocument($result['document'])] : [];
            } catch (Throwable) {
                return [];
            }
        }

        if (mb_strlen($normalized) < (int) config('counterparties.remote_min_chars', 4)) {
            return [];
        }

        try {
            $payload = $this->dadata->suggest($query, (int) config('counterparties.suggest_limit', 10));
            $items = collect($payload['suggestions'] ?? [])
                ->map(fn (array $item) => $this->storeDadata($item, detailed: false))
                ->filter()
                ->map(fn (MongoCounterparty $item) => $this->suggestionFromDocument($item))
                ->values()
                ->all();

            $this->logQuery($query, 'suggest', 'dadata', true, count($items), $started, $userId);
            return $items;
        } catch (Throwable $dadataError) {
            try {
                $items = collect($this->fns->suggest($query))
                    ->map(fn (array $item) => $this->storeFnsSuggestion($item))
                    ->filter()
                    ->map(fn (MongoCounterparty $item) => $this->suggestionFromDocument($item))
                    ->values()
                    ->all();

                $this->logQuery($query, 'suggest', 'fns', true, count($items), $started, $userId, ['dadata_error' => $dadataError->getMessage()]);
                return $items;
            } catch (Throwable $fnsError) {
                $this->logQuery($query, 'suggest', 'none', false, 0, $started, $userId, [
                    'dadata_error' => $dadataError->getMessage(),
                    'fns_error' => $fnsError->getMessage(),
                ]);
                return [];
            }
        }
    }

    public function lookup(string $query, ?int $userId = null, bool $forceRemote = false): ?array
    {
        $started = microtime(true);
        $query = trim($query);
        $digits = preg_replace('/\D+/', '', $query) ?: $query;

        $local = $this->findExact($digits);
        if ($local && ! $forceRemote && $this->isFresh($local) && $this->isDetailed($local)) {
            $this->logQuery($query, 'lookup', 'mongodb', true, 1, $started, $userId);
            return $this->resultFromDocument($local);
        }

        try {
            $payload = $this->dadata->findById($digits);
            $item = $payload['suggestions'][0] ?? null;
            if (is_array($item)) {
                $document = $this->storeDadata($item, detailed: true);
                $this->logQuery($query, 'lookup', 'dadata', true, 1, $started, $userId);
                return $this->resultFromDocument($document);
            }
        } catch (Throwable $dadataError) {
            try {
                $fns = $this->fns->company($digits);
                $document = $this->storeFnsCompany($fns);
                $this->logQuery($query, 'lookup', 'fns', true, 1, $started, $userId, ['dadata_error' => $dadataError->getMessage()]);
                return $this->resultFromDocument($document);
            } catch (Throwable $fnsError) {
                if ($local) {
                    $this->logQuery($query, 'lookup', 'mongodb_stale', true, 1, $started, $userId, [
                        'dadata_error' => $dadataError->getMessage(),
                        'fns_error' => $fnsError->getMessage(),
                    ]);
                    return $this->resultFromDocument($local);
                }

                $this->logQuery($query, 'lookup', 'none', false, 0, $started, $userId, [
                    'dadata_error' => $dadataError->getMessage(),
                    'fns_error' => $fnsError->getMessage(),
                ]);
                throw new RuntimeException('Не удалось получить данные контрагента ни из DaData, ни из API-ФНС.');
            }
        }

        if ($local) return $this->resultFromDocument($local);
        return null;
    }

    public function compare(array $profile, array $official): array
    {
        $fields = [
            'legal_type' => 'Тип покупателя',
            'company_name' => 'Краткое наименование',
            'full_company_name' => 'Полное наименование',
            'inn' => 'ИНН',
            'kpp' => 'КПП',
            'ogrn' => 'ОГРН',
            'ogrnip' => 'ОГРНИП',
            'legal_address' => 'Юридический адрес',
            'director_name' => 'Руководитель',
            'director_position' => 'Должность руководителя',
        ];

        $mismatches = [];
        foreach ($fields as $field => $label) {
            $current = $this->canonical($profile[$field] ?? null);
            $expected = $this->canonical($official[$field] ?? null);
            if ($expected !== '' && $current !== $expected) {
                $mismatches[] = compact('field', 'label') + [
                    'current' => $profile[$field] ?? null,
                    'official' => $official[$field] ?? null,
                ];
            }
        }
        return $mismatches;
    }

    private function localSuggest(string $query): array
    {
        $normalized = $this->normalize($query);
        $digits = preg_replace('/\D+/', '', $query) ?: '';
        $limit = (int) config('counterparties.suggest_limit', 10);

        $builder = MongoCounterparty::query();

        if ($digits !== '' && preg_match('/^\d+$/', trim($query))) {
            $builder->where(function ($q) use ($digits): void {
                $pattern = '/^' . preg_quote($digits, '/') . '/';

                $q->where('inn', 'regex', $pattern)
                    ->orWhere('ogrn', 'regex', $pattern);
            });
        } else {
            $tokens = $this->tokens($query);
            foreach ($tokens as $token) {
                $pattern = '/^' . preg_quote($token, '/') . '/iu';
                $builder->where('search_tokens', 'regex', $pattern);
            }
        }

        return $builder->limit($limit)->get()->map(fn ($item) => $this->suggestionFromDocument($item))->all();
    }

    private function findExact(string $query): ?MongoCounterparty
    {
        return MongoCounterparty::query()
            ->where(function ($q) use ($query): void {
                $q->where('inn', $query)->orWhere('ogrn', $query)->orWhere('identity_key', $query);
            })
            ->orderByDesc('fetched_at')
            ->first();
    }

    private function isFresh(MongoCounterparty $item): bool
    {
        return $item->fetched_at && $item->fetched_at->gte(now()->subDays((int) config('counterparties.fresh_days', 30)));
    }

    private function isDetailed(MongoCounterparty $item): bool
    {
        return (bool) data_get($item->provider_meta, 'detailed', false);
    }

    private function storeDadata(array $item, bool $detailed): ?MongoCounterparty
    {
        $data = $item['data'] ?? null;
        if (! is_array($data) || empty($data['inn'])) return null;

        $profile = $this->profileFromDadata($item);
        $inn = (string) ($data['inn'] ?? '');
        $kpp = $data['kpp'] ?? null;
        $ogrn = $data['ogrn'] ?? null;
        $identityKey = $inn . ':' . ($kpp ?: 'main');

        $document = MongoCounterparty::query()->firstOrNew(['identity_key' => $identityKey]);
        $alreadyDetailed = $document->exists && $this->isDetailed($document);

        $summary = [
            'identity_key' => $identityKey,
            'hid' => $data['hid'] ?? $document->hid,
            'type' => $data['type'] ?? $document->type,
            'inn' => $inn,
            'kpp' => $kpp,
            'ogrn' => $ogrn,
            'name' => $profile['company_name'] ?? ($item['value'] ?? $document->name),
            'full_name' => $profile['full_company_name'] ?? ($item['unrestricted_value'] ?? $document->full_name),
            'name_normalized' => $this->normalize($profile['company_name'] ?? ($item['value'] ?? $document->name ?? '')),
            'search_tokens' => $this->buildSearchTokens($item, $profile),
            'status' => $data['state']['status'] ?? $document->status,
            'source' => 'dadata',
        ];

        // Обычная подсказка не должна затирать уже сохранённую подробную карточку.
        if (! $detailed && $alreadyDetailed) {
            $document->fill($summary)->save();
            return $document;
        }

        $document->fill($summary + [
            'address' => $profile['legal_address'] ?? null,
            'director_name' => $profile['director_name'] ?? null,
            'director_position' => $profile['director_position'] ?? null,
            'profile' => $profile,
            'raw' => $item,
            'provider_meta' => ['detailed' => $detailed],
            'fetched_at' => now(),
            'actuality_at' => $this->dateFromMillis($data['state']['actuality_date'] ?? null),
        ])->save();

        return $document;
    }

    private function storeFnsCompany(array $result): MongoCounterparty
    {
        $profile = $result['profile'];
        $inn = (string) ($profile['inn'] ?? '');
        $kpp = $profile['kpp'] ?? null;
        $identityKey = $inn . ':' . ($kpp ?: 'main');

        $document = MongoCounterparty::query()->firstOrNew(['identity_key' => $identityKey]);
        $document->fill([
            'identity_key' => $identityKey,
            'type' => ($profile['legal_type'] ?? null) === 'individual_entrepreneur' ? 'INDIVIDUAL' : 'LEGAL',
            'inn' => $inn,
            'kpp' => $kpp,
            'ogrn' => $profile['ogrn'] ?? $profile['ogrnip'] ?? null,
            'name' => $profile['company_name'] ?? null,
            'full_name' => $profile['full_company_name'] ?? null,
            'name_normalized' => $this->normalize($profile['company_name'] ?? ''),
            'search_tokens' => $this->tokens(implode(' ', array_filter([$profile['company_name'] ?? null, $profile['full_company_name'] ?? null, $profile['legal_address'] ?? null]))),
            'status' => $profile['fns_status'] ?? null,
            'address' => $profile['legal_address'] ?? null,
            'director_name' => $profile['director_name'] ?? null,
            'director_position' => $profile['director_position'] ?? null,
            'profile' => $profile,
            'source' => 'fns',
            'raw' => $result['raw'] ?? null,
            'fetched_at' => now(),
        ])->save();

        return $document;
    }

    private function storeFnsSuggestion(array $item): ?MongoCounterparty
    {
        if (empty($item['inn'])) return null;
        $identityKey = $item['inn'] . ':' . (($item['kpp'] ?? null) ?: 'main');
        $document = MongoCounterparty::query()->firstOrNew(['identity_key' => $identityKey]);
        $document->fill([
            'identity_key' => $identityKey,
            'type' => $item['type'] ?? null,
            'inn' => $item['inn'],
            'kpp' => $item['kpp'] ?? null,
            'ogrn' => $item['ogrn'] ?? null,
            'name' => $item['name'] ?? null,
            'name_normalized' => $this->normalize($item['name'] ?? ''),
            'search_tokens' => $this->tokens(implode(' ', array_filter([$item['name'] ?? null, $item['address'] ?? null]))),
            'status' => $item['status'] ?? null,
            'address' => $item['address'] ?? null,
            'source' => 'fns',
            'raw' => $item,
            'fetched_at' => now(),
        ])->save();
        return $document;
    }

    private function profileFromDadata(array $item): array
    {
        $data = $item['data'] ?? [];
        $isIp = ($data['type'] ?? null) === 'INDIVIDUAL';
        $fio = $data['fio'] ?? [];
        $fioText = trim(implode(' ', array_filter([$fio['surname'] ?? null, $fio['name'] ?? null, $fio['patronymic'] ?? null])));

        if ($isIp) {
            return [
                'legal_type' => 'individual_entrepreneur',
                'company_name' => $item['value'] ?? ($fioText ? 'ИП ' . $fioText : null),
                'full_company_name' => $item['unrestricted_value'] ?? ($fioText ? 'Индивидуальный предприниматель ' . $fioText : null),
                'inn' => $data['inn'] ?? null,
                'kpp' => null,
                'ogrn' => null,
                'ogrnip' => $data['ogrn'] ?? null,
                'legal_address' => $this->dadataAddress($data),
                'director_name' => $fioText ?: null,
                'director_position' => 'Индивидуальный предприниматель',
                'fns_status' => $data['state']['status'] ?? null,
            ];
        }

        return [
            'legal_type' => 'legal_entity',
            'company_name' => $data['name']['short_with_opf'] ?? $item['value'] ?? null,
            'full_company_name' => $data['name']['full_with_opf'] ?? $item['unrestricted_value'] ?? null,
            'inn' => $data['inn'] ?? null,
            'kpp' => $data['kpp'] ?? null,
            'ogrn' => $data['ogrn'] ?? null,
            'ogrnip' => null,
            'legal_address' => $this->dadataAddress($data),
            'director_name' => $data['management']['name'] ?? null,
            'director_position' => $data['management']['post'] ?? null,
            'fns_status' => $data['state']['status'] ?? null,
        ];
    }

    private function dadataAddress(array $data): ?string
    {
        $address = $data['address'] ?? null;

        if (is_string($address)) {
            return trim($address) ?: null;
        }

        if (! is_array($address)) {
            return null;
        }

        foreach ([
            $address['unrestricted_value'] ?? null,
            $address['value'] ?? null,
            $address['data']['source'] ?? null,
        ] as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function resultFromDocument(MongoCounterparty $document): array
    {
        return [
            'profile' => (array) ($document->profile ?? []),
            'raw' => $document->raw,
            'source' => $document->source,
            'document' => $document,
            'fetched_at' => $document->fetched_at,
        ];
    }

    private function suggestionFromDocument(MongoCounterparty $item): array
    {
        return [
            'type' => $item->type,
            'inn' => $item->inn,
            'kpp' => $item->kpp,
            'ogrn' => $item->ogrn,
            'name' => $item->name,
            'address' => $item->address,
            'status' => $item->status,
            'source' => $item->source,
        ];
    }

    private function buildSearchTokens(array $item, array $profile): array
    {
        return $this->tokens(implode(' ', array_filter([
            $item['value'] ?? null,
            $item['unrestricted_value'] ?? null,
            $profile['company_name'] ?? null,
            $profile['full_company_name'] ?? null,
            $profile['legal_address'] ?? null,
            $profile['director_name'] ?? null,
        ])));
    }

    private function tokens(string $value): array
    {
        return collect(preg_split('/[^\pL\pN]+/u', $this->normalize($value)) ?: [])
            ->filter(fn ($v) => mb_strlen($v) >= 2)
            ->unique()->values()->all();
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));
    }

    private function canonical(mixed $value): string
    {
        return mb_strtolower(preg_replace('/[\s\.,"«»\'\-]+/u', '', trim((string) $value)) ?? '');
    }

    private function isExactIdentifier(string $value): bool
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        return in_array(strlen($digits), [10, 12, 13, 15], true);
    }

    private function dateFromMillis(mixed $value): ?\Illuminate\Support\Carbon
    {
        if (! is_numeric($value)) return null;
        return \Illuminate\Support\Carbon::createFromTimestampMs((int) $value);
    }

    private function logQuery(string $query, string $kind, string $source, bool $ok, int $count, float $started, ?int $userId, array $meta = []): void
    {
        try {
            MongoCounterpartyQuery::query()->create([
                'query' => $query,
                'query_normalized' => $this->normalize($query),
                'kind' => $kind,
                'source' => $source,
                'ok' => $ok,
                'result_count' => $count,
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                'user_id' => $userId,
                'meta' => $meta,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Журнал не должен ломать пользовательский поиск.
        }
    }
}
