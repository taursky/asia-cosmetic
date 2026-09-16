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
                $ref = trim((string) ($item['ref'] ?? ''));
                $code = trim((string) ($item['code'] ?? ''));
                $name = trim((string) ($item['name'] ?? ''));

                if ($ref === '') {
                    throw ValidationException::withMessages([
                        'items' => 'Для склада обязательно поле ref (GUID 1С).',
                    ]);
                }

                if ($name === '') {
                    throw ValidationException::withMessages([
                        'items' => "Для склада {$ref} обязательно поле name.",
                    ]);
                }

                $warehouse = Warehouse::query()
                    ->where('one_c_id', $ref)
                    ->first();

                if (! $warehouse && $code !== '') {
                    $warehouse = Warehouse::query()
                        ->where('code', $code)
                        ->first();
                }

                $created = ! $warehouse;
                $warehouse ??= new Warehouse();

                $warehouse->fill([
                    'one_c_id' => $ref,
                    'external_id' => $item['external_id'] ?? $warehouse->external_id,
                    'code' => $code !== '' ? $code : $warehouse->code,
                    'name' => $name,
                    'is_active' => (bool) ($item['active'] ?? true),
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                    'synced_at' => now(),
                ]);

                $warehouse->save();

                $stats['processed']++;
                $stats[$created ? 'created' : 'updated']++;
            }
        });

        return $stats;
    }
}
