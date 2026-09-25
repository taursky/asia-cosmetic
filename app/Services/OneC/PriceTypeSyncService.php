<?php

namespace App\Services\OneC;

use App\DTO\OneC\SyncResult;
use App\Models\ProductPriceType;
use Illuminate\Support\Str;
use Throwable;

class PriceTypeSyncService
{
    public function sync(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $index => $row) {
            $result->processed++;

            try {
                $ref = trim((string) ($row['ref'] ?? $row['one_c_id'] ?? ''));
                $code = trim((string) ($row['code'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));

                if ($ref === '' && $code === '') {
                    throw new \InvalidArgumentException('Для типа цены обязателен ref или code.');
                }

                if ($name === '') {
                    throw new \InvalidArgumentException('Для типа цены обязательно поле name.');
                }

                $type = null;

                if ($ref !== '') {
                    $type = ProductPriceType::query()->where('one_c_id', $ref)->first();
                }

                if (! $type && $code !== '') {
                    $type = ProductPriceType::query()->where('code', $code)->first();
                }

                $type ??= new ProductPriceType();

                $exists = $type->exists;

                $type->fill([
                    'one_c_id' => $ref !== '' ? $ref : $type->one_c_id,
                    'code' => $code !== '' ? $code : ($type->code ?: Str::slug($name, '_')),
                    'name' => $name,
                ])->save();

                $exists ? $result->updated++ : $result->created++;
            } catch (Throwable $e) {
                $result->errors[] = [
                    'index' => $index,
                    'ref' => $row['ref'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }
}
