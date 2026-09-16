<?php

namespace App\Services\OneC;

use App\Models\ProductVariant;
use App\Models\ProductVariantStock;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockSyncService
{
    public function sync(array $items): array
    {
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'variants_recalculated' => 0,
        ];

        $variantIds = [];

        DB::transaction(function () use ($items, &$stats, &$variantIds): void {
            foreach ($items as $item) {
                $variantRef = trim((string) ($item['variant_ref'] ?? ''));
                $warehouseRef = trim((string) ($item['warehouse_ref'] ?? ''));

                if ($variantRef === '' || $warehouseRef === '') {
                    throw ValidationException::withMessages([
                        'items' => 'Для остатка обязательны variant_ref и warehouse_ref.',
                    ]);
                }

                $variant = ProductVariant::withTrashed()
                    ->where('one_c_id', $variantRef)
                    ->first();

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'items' => "SKU с GUID {$variantRef} не найден.",
                    ]);
                }

                $warehouse = Warehouse::query()
                    ->where('one_c_id', $warehouseRef)
                    ->first();

                if (! $warehouse) {
                    throw ValidationException::withMessages([
                        'items' => "Склад с GUID {$warehouseRef} не найден. Сначала синхронизируйте warehouses.",
                    ]);
                }

                $quantity = (float) ($item['quantity'] ?? 0);
                $reserved = (float) ($item['reserved'] ?? 0);
                $available = array_key_exists('available', $item)
                    ? (float) $item['available']
                    : max(0, $quantity - $reserved);

                $stock = ProductVariantStock::query()
                    ->where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->first();

                $created = ! $stock;
                $stock ??= new ProductVariantStock([
                    'product_variant_id' => $variant->id,
                    'warehouse_id' => $warehouse->id,
                ]);

                $stock->fill([
                    'quantity' => $quantity,
                    'reserved' => $reserved,
                    'available' => $available,
                    'synced_at' => now(),
                ]);

                $stock->save();

                $variantIds[$variant->id] = $variant->id;

                $stats['processed']++;
                $stats[$created ? 'created' : 'updated']++;
            }

            foreach ($variantIds as $variantId) {
                $this->recalculateVariantStock($variantId);
                $stats['variants_recalculated']++;
            }
        });

        return $stats;
    }

    public function recalculateVariantStock(int $variantId): void
    {
        $totalAvailable = ProductVariantStock::query()
            ->where('product_variant_id', $variantId)
            ->whereHas('warehouse', fn ($query) => $query->where('is_active', true))
            ->sum('available');

        ProductVariant::withTrashed()
            ->whereKey($variantId)
            ->update([
                'stock' => $totalAvailable,
                'synced_at' => now(),
            ]);
    }
}
