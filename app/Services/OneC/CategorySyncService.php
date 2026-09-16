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

        foreach ($items as $index => $data) {
            $result->processed++;
            try {
                DB::transaction(function () use ($data, $locale, $result): void {
                    $oneCId = $data['ref'] ?? $data['one_c_id'] ?? null;
                    if (! $oneCId) {
                        throw new \InvalidArgumentException('Category ref is required.');
                    }

                    $category = Category::query()->firstOrNew(['one_c_id' => $oneCId]);
                    $exists = $category->exists;

                    $parentId = null;
                    if (! empty($data['parent_ref'])) {
                        $parentId = Category::query()->where('one_c_id', $data['parent_ref'])->value('id');
                    }

                    $category->fill([
                        'parent_id' => $parentId,
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
                            ]
                        );
                    }

                    $exists ? $result->updated++ : $result->created++;
                });
            } catch (Throwable $e) {
                $result->errors[] = ['index' => $index, 'ref' => $data['ref'] ?? null, 'message' => $e->getMessage()];
            }
        }

        return $result;
    }
}
