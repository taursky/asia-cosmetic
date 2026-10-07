<?php

namespace App\Services\OneC;

use App\DTO\OneC\SyncResult;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceType;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PriceSyncService
{
    public function sync(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $index => $row) {
            $result->processed++;

            try {
                $source = $this->source($row);
                $type = $this->resolvePriceType($row);
                $productRef = $this->normalizeRef($row['product_ref'] ?? null);

                if (! $productRef) {
                    throw new RuntimeException('Для цены не указан product_ref.');
                }

                $product = Product::query()
                    ->where('source', $source)
                    ->where('one_c_id', $productRef)
                    ->first();

                if (! $product) {
                    throw new RuntimeException("Товар {$source}:{$productRef} не найден. Сначала синхронизируйте товары.");
                }

                $variantRef = $this->normalizeRef($row['variant_ref'] ?? null);
                $variant = null;

                if ($variantRef) {
                    $variant = ProductVariant::query()
                        ->where('source', $source)
                        ->where('one_c_id', $variantRef)
                        ->where('product_id', $product->id)
                        ->first();

                    if (! $variant) {
                        throw new RuntimeException("Вариант {$source}:{$variantRef} для товара {$productRef} не найден.");
                    }
                }

                $key = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_price_type_id' => $type->id,
                    'min_quantity' => (int) ($row['min_quantity'] ?? 1),
                ];

                $price = ProductPrice::query()->firstOrNew($key);
                $exists = $price->exists;
                $exchangeRef = trim((string) ($row['ref'] ?? ''));

                $price->fill([
                    'amount' => $row['amount'],
                    'old_amount' => $row['old_amount'] ?? null,
                    'currency' => $row['currency'] ?? 'RUB',
                    'external_id' => $exchangeRef !== '' ? $exchangeRef : ($row['external_id'] ?? $price->external_id),
                    'one_c_id' => $this->isUuid($exchangeRef) ? $exchangeRef : $price->one_c_id,
                    'valid_from' => $row['valid_from'] ?? null,
                    'valid_until' => $row['valid_until'] ?? null,
                    'synced_at' => now(),
                ])->save();

                $exists ? $result->updated++ : $result->created++;
            } catch (Throwable $e) {
                $result->errors[] = [
                    'index' => $index,
                    'source' => $row['source'] ?? null,
                    'ref' => $row['ref'] ?? null,
                    'product_ref' => $row['product_ref'] ?? null,
                    'variant_ref' => $row['variant_ref'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    private function normalizeRef(mixed $ref): ?string
    {
        if (! is_string($ref)) return null;
        $ref = trim($ref);
        return $ref !== '' ? $ref : null;
    }

    private function source(array $row): string
    {
        return trim((string) ($row['source'] ?? config('onec.source', '1c-unf'))) ?: '1c-unf';
    }

    private function isUuid(?string $value): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }

    private function resolvePriceType(array $row): ProductPriceType
    {
        $type = null;

        if (! empty($row['price_type_ref'])) {
            $type = ProductPriceType::query()->where('one_c_id', $row['price_type_ref'])->first();
        }

        if (! $type && ! empty($row['price_type_code'])) {
            $type = ProductPriceType::query()->where('code', $row['price_type_code'])->first();
        }

        if (! $type) {
            $name = trim((string) ($row['price_type_name'] ?? 'Цена'));
            $type = ProductPriceType::query()->create([
                'code' => $row['price_type_code'] ?? (Str::slug($name, '_') ?: 'price_' . Str::lower(Str::random(8))),
                'name' => $name,
                'one_c_id' => $row['price_type_ref'] ?? null,
            ]);
        } else {
            $type->fill([
                'one_c_id' => $row['price_type_ref'] ?? $type->one_c_id,
                'code' => $row['price_type_code'] ?? $type->code,
                'name' => $row['price_type_name'] ?? $type->name,
            ])->save();
        }

        return $type;
    }
}
