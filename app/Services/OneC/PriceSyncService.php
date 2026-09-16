<?php

namespace App\Services\OneC;

use App\DTO\OneC\SyncResult;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceType;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Throwable;

class PriceSyncService
{
    public function sync(array $items): SyncResult
    {
        $result = new SyncResult();
        foreach ($items as $index => $row) {
            $result->processed++;
            try {
                $product = Product::query()->where('one_c_id', $row['product_ref'] ?? null)->firstOrFail();
                $variant = ! empty($row['variant_ref'])
                    ? ProductVariant::query()->where('one_c_id', $row['variant_ref'])->where('product_id', $product->id)->firstOrFail()
                    : null;

                $type = null;
                if (! empty($row['price_type_ref'])) $type = ProductPriceType::query()->where('one_c_id', $row['price_type_ref'])->first();
                if (! $type && ! empty($row['price_type_code'])) $type = ProductPriceType::query()->where('code', $row['price_type_code'])->first();
                if (! $type) {
                    $name = $row['price_type_name'] ?? 'Цена';
                    $type = ProductPriceType::create([
                        'code' => $row['price_type_code'] ?? Str::slug($name, '_'),
                        'name' => $name,
                        'one_c_id' => $row['price_type_ref'] ?? null,
                    ]);
                }

                $key = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_price_type_id' => $type->id,
                    'min_quantity' => $row['min_quantity'] ?? 1,
                ];
                $price = ProductPrice::query()->firstOrNew($key);
                $exists = $price->exists;
                $price->fill([
                    'amount' => $row['amount'],
                    'old_amount' => $row['old_amount'] ?? null,
                    'currency' => $row['currency'] ?? 'RUB',
                    'external_id' => $row['external_id'] ?? null,
                    'one_c_id' => $row['ref'] ?? null,
                    'valid_from' => $row['valid_from'] ?? null,
                    'valid_until' => $row['valid_until'] ?? null,
                    'synced_at' => now(),
                ])->save();
                $exists ? $result->updated++ : $result->created++;
            } catch (Throwable $e) {
                $result->errors[] = ['index' => $index, 'message' => $e->getMessage()];
            }
        }
        return $result;
    }
}
