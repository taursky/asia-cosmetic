<?php

namespace App\Services\OneC;

use App\DTO\OneC\SyncResult;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CategorySyncService
{
    public function sync(array $items): SyncResult
    {
        $result = new SyncResult();
        $locale = (string) config('onec.locale', 'ru');
        $parents = [];

        // Первый проход: создаём/обновляем все категории без зависимости от порядка дерева.
        foreach ($items as $index => $data) {
            $result->processed++;

            try {
                DB::transaction(function () use ($data, $locale, $result, &$parents): void {
                    $siteUid = trim((string) ($data['ref'] ?? $data['one_c_id'] ?? ''));
                    if ($siteUid === '') {
                        throw new \InvalidArgumentException('Category ref/site UID is required.');
                    }

                    $category = Category::query()->firstOrNew(['one_c_id' => $siteUid]);
                    $exists = $category->exists;

                    $category->fill([
                        'external_id' => $data['external_id'] ?? $category->external_id,
                        'is_active' => (bool) ($data['active'] ?? true),
                        'sort_order' => (int) ($data['sort_order'] ?? 0),
                    ])->save();

                    $name = trim((string) ($data['name'] ?? ''));
                    if ($name !== '') {
                        $category->langs()->updateOrCreate(
                            ['lang' => $data['lang'] ?? $locale],
                            [
                                'name' => $name,
                                'slug' => $data['slug'] ?? Str::slug($name),
                                'description' => $data['description'] ?? null,
                                'seo_title' => $data['seo_title'] ?? null,
                                'seo_description' => $data['seo_description'] ?? null,
                            ],
                        );
                    }

                    $parents[$siteUid] = trim((string) ($data['parent_ref'] ?? '')) ?: null;
                    $exists ? $result->updated++ : $result->created++;
                });
            } catch (Throwable $e) {
                $result->errors[] = [
                    'index' => $index,
                    'ref' => $data['ref'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Второй проход: выставляем parent_id, когда все категории уже существуют.
        foreach ($parents as $siteUid => $parentUid) {
            $parentId = $parentUid
                ? Category::query()->where('one_c_id', $parentUid)->value('id')
                : null;

            Category::query()
                ->where('one_c_id', $siteUid)
                ->update(['parent_id' => $parentId]);
        }

        return $result;
    }
}
