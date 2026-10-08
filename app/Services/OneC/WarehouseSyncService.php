<?php

namespace App\Services\OneC;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseSyncService
{
    public function sync(array $items): array
    {
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
        ];

        DB::transaction(function () use ($items, &$stats): void {
            foreach ($items as $item) {
                $source = trim((string) (
                    $item['source']
                    ?? config('onec.source', '1c-unf')
                )) ?: '1c-unf';

                $ref = trim((string) ($item['ref'] ?? ''));
                $code = trim((string) ($item['code'] ?? ''));
                $name = trim((string) ($item['name'] ?? ''));

                if ($ref === '') {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Для склада обязательно поле ref (GUID 1С).',
                    ]);
                }

                if ($name === '') {
                    throw ValidationException::withMessages([
                        'items' =>
                            "Для склада {$ref} обязательно поле name.",
                    ]);
                }

                /*
                 * 1. Основной вариант:
                 * ищем склад по источнику и GUID 1С.
                 */
                $warehouse = Warehouse::query()
                    ->where('source', $source)
                    ->where('one_c_id', $ref)
                    ->first();

                /*
                 * 2. Совместимость со складами,
                 * которые были созданы старым обменом.
                 *
                 * warehouses.code имеет UNIQUE индекс,
                 * поэтому перед созданием обязательно
                 * проверяем существующий code.
                 */
                if (! $warehouse && $code !== '') {
                    $warehouse = Warehouse::query()
                        ->where('code', $code)
                        ->first();
                }

                $created = ! $warehouse;

                if (! $warehouse) {
                    $warehouse = new Warehouse();
                }

                /*
                 * После обнаружения старого склада
                 * закрепляем за ним текущие source/GUID 1С.
                 */
                $warehouse->fill([
                    'source' => $source,
                    'one_c_id' => $ref,

                    'external_id' =>
                        $item['external_id']
                        ?? $warehouse->external_id,

                    'code' =>
                        $code !== ''
                            ? $code
                            : $warehouse->code,

                    'name' => $name,

                    'is_active' =>
                        (bool) ($item['active'] ?? true),

                    'sort_order' =>
                        (int) ($item['sort_order'] ?? 0),

                    'synced_at' => now(),
                ]);

                $warehouse->save();

                $stats['processed']++;

                if ($created) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            }
        });

        return $stats;
    }
}
