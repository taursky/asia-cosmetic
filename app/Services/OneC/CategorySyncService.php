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

        /*
         * v1.4:
         *
         * ref          = стабильный UID категории сайта.
         *                Одинаковый во всех базах 1С.
         *
         * external_id  = идентификатор категории конкретной базы 1С.
         *
         * categories.sync_uid = ref
         * categories.one_c_id  = external_id
         */

        foreach ($items as $index => $data) {
            $result->processed++;

            try {
                DB::transaction(function () use (
                    $data,
                    $locale,
                    $result,
                    &$parents
                ): void {

                    $siteUid = trim((string) ($data['ref'] ?? ''));

                    if ($siteUid === '') {
                        throw new \InvalidArgumentException(
                            'Category ref/site UID is required.'
                        );
                    }

                    $oneCId = trim(
                        (string) ($data['external_id'] ?? '')
                    );

                    /*
                     * Сначала ищем по глобальному UID сайта.
                     */
                    $category = Category::query()
                        ->where('sync_uid', $siteUid)
                        ->first();

                    /*
                     * Переходный режим.
                     *
                     * Если эта категория раньше была загружена старым
                     * обменом, site UID мог оказаться в one_c_id.
                     */
                    if (! $category) {
                        $category = Category::query()
                            ->where('one_c_id', $siteUid)
                            ->whereNull('sync_uid')
                            ->first();
                    }

                    /*
                     * Если 1С передала свой GUID, пробуем найти существующую
                     * категорию старого обмена по нему.
                     */
                    if (! $category && $oneCId !== '') {
                        $category = Category::query()
                            ->where('one_c_id', $oneCId)
                            ->first();
                    }

                    if (! $category) {
                        $category = new Category();
                    }

                    $exists = $category->exists;

                    /*
                     * Важное место:
                     * ref теперь сохраняется именно в sync_uid.
                     */
                    $category->sync_uid = $siteUid;

                    if ($oneCId !== '') {
                        $category->one_c_id = $oneCId;
                    }

                    /*
                     * external_id оставляем для исходного значения,
                     * если оно используется проектом отдельно.
                     */
                    $category->external_id =
                        $data['external_id']
                        ?? $category->external_id;

                    $category->is_active =
                        (bool) ($data['active'] ?? true);

                    $category->sort_order =
                        (int) ($data['sort_order'] ?? 0);

                    $category->save();

                    /*
                     * Перевод.
                     */
                    $name = trim((string) ($data['name'] ?? ''));

                    if ($name !== '') {
                        $lang = (string) ($data['lang'] ?? $locale);

                        $category->langs()->updateOrCreate(
                            [
                                'lang' => $lang,
                            ],
                            [
                                'name' => $name,
                                'slug' => $data['slug']
                                    ?? Str::slug($name),
                                'description' =>
                                    $data['description'] ?? null,
                                'seo_title' =>
                                    $data['seo_title'] ?? null,
                                'seo_description' =>
                                    $data['seo_description'] ?? null,
                            ],
                        );
                    }

                    /*
                     * parent_ref также является site UID,
                     * поэтому во втором проходе будем искать родителя
                     * через sync_uid.
                     */
                    $parentUid = trim(
                        (string) ($data['parent_ref'] ?? '')
                    );

                    $parents[$siteUid] =
                        $parentUid !== ''
                            ? $parentUid
                            : null;

                    if ($exists) {
                        $result->updated++;
                    } else {
                        $result->created++;
                    }
                });

            } catch (Throwable $e) {

                \Log::error('1C category sync failed', [
                    'index' => $index,
                    'ref' => $data['ref'] ?? null,
                    'external_id' => $data['external_id'] ?? null,
                    'name' => $data['name'] ?? null,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $result->errors[] = [
                    'index' => $index,
                    'ref' => $data['ref'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        /*
         * Второй проход дерева.
         *
         * parent_ref и ref — UID сайта,
         * поэтому оба ищем по sync_uid.
         */
        foreach ($parents as $siteUid => $parentUid) {

            $parentId = null;

            if ($parentUid) {
                $parentId = Category::query()
                    ->where('sync_uid', $parentUid)
                    ->value('id');
            }

            Category::query()
                ->where('sync_uid', $siteUid)
                ->update([
                    'parent_id' => $parentId,
                ]);
        }

        return $result;
    }
}
