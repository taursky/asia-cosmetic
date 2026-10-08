<?php

namespace App\Services\OneC;

use App\DTO\OneC\SyncResult;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CatalogSyncService
{
    public function sync(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $index => $data) {
            $result->processed++;

            try {
                DB::transaction(fn () => $this->syncProduct($data, $result));
            } catch (Throwable $e) {

                Log::error('1C product sync failed', [
                    'index' => $index,
                    'ref' => $data['ref'] ?? null,
                    'source' => $data['source'] ?? null,
                    'name' => $data['name'] ?? null,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $result->errors[] = [
                    'index' => $index,
                    'ref' => $data['ref'] ?? null,
                    'source' => $data['source'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    private function syncProduct(array $data, SyncResult $result): void
    {
        $locale = (string) config('onec.locale', 'ru');
        $source = $this->source($data);
        $oneCId = trim((string) ($data['ref'] ?? $data['one_c_id'] ?? ''));

        if ($oneCId === '') {
            throw new \InvalidArgumentException('Product ref is required.');
        }

        $product = Product::withTrashed()
            ->where('source', $source)
            ->where('one_c_id', $oneCId)
            ->first() ?? new Product();

        $exists = $product->exists;

        $product->fill([
            'source' => $source,
            'one_c_id' => $oneCId,
            'sync_uid' => $data['sync_uid'] ?? $product->sync_uid,
            'external_id' => $data['external_id'] ?? $product->external_id,
            'sku' => $data['sku'] ?? $product->sku,
            'is_active' => (bool) ($data['active'] ?? true),
            'vat_rate' => $data['vat_rate'] ?? $product->vat_rate,
            'unit' => $data['unit'] ?? $product->unit,
            'weight' => $data['weight'] ?? $product->weight,
            'length' => $data['length'] ?? $product->length,
            'width' => $data['width'] ?? $product->width,
            'height' => $data['height'] ?? $product->height,
            'synced_at' => now(),
        ])->save();

        if (method_exists($product, 'trashed') && $product->trashed()) {
            $product->restore();
        }

        if (! empty($data['name'])) {
            $lang = (string) ($data['lang'] ?? $locale);

            $product->langs()->updateOrCreate(['lang' => $lang], [
                'name' => $data['name'],
                'slug' => $this->uniqueProductSlug(
                    $product,
                    $lang,
                    (string) ($data['slug'] ?? $data['name'])
                ),
                'short_description' => array_key_exists('short_description', $data)
                    ? $data['short_description']
                    : $product->langs()->where('lang', $lang)->value('short_description'),
                'description' => array_key_exists('description', $data)
                    ? $data['description']
                    : $product->langs()->where('lang', $lang)->value('description'),
            ]);
        }

        $this->syncCategories($product, $data['categories'] ?? []);
        $this->syncAttributes($product, $data['attributes'] ?? [], $locale);
        $this->syncVariants($product, $data['variants'] ?? [], $locale, $source);

        $exists ? $result->updated++ : $result->created++;
    }

    private function syncCategories(Product $product, array $categories): void
    {
        $ids = collect($categories)
            ->pluck('ref')
            ->filter()
            ->map(function ($ref) {
                return Category::query()
                    ->where(function ($query) use ($ref) {
                        $query->where('sync_uid', $ref)
                            ->orWhere('one_c_id', $ref);
                    })
                    ->value('id');
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Важно: sync([]) удаляет старые связи, если менеджер убрал товар из всех категорий в 1С.
        $product->categories()->sync($ids);
    }

    private function syncAttributes(Product $product, array $attributes, string $locale): void
    {
        $valueIds = [];

        foreach ($attributes as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($name === '' || $value === '') {
                continue;
            }

            $attribute = $this->findOrCreateAttribute($row, $name, $locale);
            $attributeValue = $this->findOrCreateAttributeValue($attribute, $row, $value, $locale);
            $valueIds[] = $attributeValue->id;
        }

        $product->attributeValues()->sync(array_values(array_unique($valueIds)));
    }

    private function findOrCreateAttribute(array $row, string $name, string $locale): Attribute
    {
        $attribute = null;

        if (! empty($row['attribute_ref'])) {
            $attribute = Attribute::query()->where('one_c_id', $row['attribute_ref'])->first();
        }

        $attribute ??= Attribute::query()
            ->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('name', $name))
            ->first();

        if (! $attribute) {
            $attribute = Attribute::create([
                'code' => $this->safeCode(
                    $name,
                    'attr',
                    (string) ($row['attribute_ref'] ?? '')
                ),
                'type' => $row['type'] ?? 'select',
                'one_c_id' => $row['attribute_ref'] ?? null,
                'is_filterable' => (bool) ($row['is_filterable'] ?? true),
                'is_searchable' => true,
            ]);
            $attribute->langs()->create(['lang' => $locale, 'name' => $name]);
        }

        return $attribute;
    }

    private function findOrCreateAttributeValue(Attribute $attribute, array $row, string $value, string $locale): AttributeValue
    {
        $model = null;

        if (! empty($row['value_ref'])) {
            $model = AttributeValue::query()->where('one_c_id', $row['value_ref'])->first();
        }

        $model ??= $attribute->values()
            ->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('value', $value))
            ->first();

        if (! $model) {
            $model = $attribute->values()->create([
                'code' => $this->safeCode(
                    $value,
                    'value',
                    (string) ($row['value_ref'] ?? '')
                ),
                'one_c_id' => $row['value_ref'] ?? null,
            ]);
            $model->langs()->create(['lang' => $locale, 'value' => $value]);
        }

        return $model;
    }

    private function syncVariants(Product $product, array $variants, string $locale, string $source): void
    {
        foreach ($variants as $row) {
            $ref = trim((string) ($row['ref'] ?? $row['one_c_id'] ?? ''));
            if ($ref === '') {
                continue;
            }

            $variant = ProductVariant::withTrashed()
                ->where('source', $source)
                ->where('one_c_id', $ref)
                ->first() ?? new ProductVariant();

            $variant->fill([
                'source' => $source,
                'product_id' => $product->id,
                'one_c_id' => $ref,
                'external_id' => $row['external_id'] ?? $variant->external_id,
                'sku' => $row['sku'] ?? $variant->sku,
                'barcode' => $row['barcode'] ?? $variant->barcode,
                'is_active' => (bool) ($row['active'] ?? true),
                'stock' => $row['stock'] ?? $variant->stock ?? 0,
                'weight' => $row['weight'] ?? $variant->weight,
                'length' => $row['length'] ?? $variant->length,
                'width' => $row['width'] ?? $variant->width,
                'height' => $row['height'] ?? $variant->height,
                'synced_at' => now(),
            ])->save();

            if (method_exists($variant, 'trashed') && $variant->trashed()) {
                $variant->restore();
            }

            if (! empty($row['name'])) {
                $variant->langs()->updateOrCreate(['lang' => $row['lang'] ?? $locale], [
                    'name' => $row['name'],
                    'value' => $row['value'] ?? null,
                    'description' => $row['description'] ?? null,
                ]);
            }

            $this->syncVariantOptions($product, $variant, $row['options'] ?? [], $locale);
        }
    }

    private function syncVariantOptions(Product $product, ProductVariant $variant, array $options, string $locale): void
    {
        $valueIds = [];

        foreach ($options as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($name === '' || $value === '') {
                continue;
            }

            $option = null;
            if (! empty($row['option_ref'])) {
                $option = Option::query()->where('one_c_id', $row['option_ref'])->first();
            }

            $option ??= Option::query()
                ->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('name', $name))
                ->first();

            if (! $option) {
                $option = Option::create([
                    'code' => $this->safeCode(
                        $name,
                        'option',
                        (string) ($row['option_ref'] ?? '')
                    ),
                    'one_c_id' => $row['option_ref'] ?? null,
                ]);
                $option->langs()->create(['lang' => $locale, 'name' => $name]);
            }

            $optionValue = null;
            if (! empty($row['value_ref'])) {
                $optionValue = OptionValue::query()->where('one_c_id', $row['value_ref'])->first();
            }

            $optionValue ??= $option->values()
                ->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('value', $value))
                ->first();

            if (! $optionValue) {
                $optionValue = $option->values()->create([
                    'code' => $this->safeCode(
                        $value,
                        'value',
                        (string) ($row['value_ref'] ?? '')
                    ),
                    'one_c_id' => $row['value_ref'] ?? null,
                ]);
                $optionValue->langs()->create(['lang' => $locale, 'value' => $value]);
            }

            $valueIds[] = $optionValue->id;
        }

        $valueIds = array_values(array_unique($valueIds));
        $variant->optionValues()->sync($valueIds);

        if ($valueIds !== []) {
            $product->optionValues()->syncWithoutDetaching($valueIds);
        }
    }

    /**
     * Stable short code for DB columns such as attribute_values.code.
     *
     * Long free-text 1C properties ("Состав", "Способ применения", etc.)
     * must never be transliterated in full into code: that easily exceeds
     * VARCHAR limits. We keep a readable prefix and add a deterministic hash.
     */
    private function safeCode(string $value, string $prefix, string $externalRef = ''): string
    {
        $slug = Str::slug($value, '_');

        if ($slug === '') {
            $slug = $prefix;
        }

        // Keep plenty of headroom even if the DB column is VARCHAR(191/255).
        if (mb_strlen($slug) <= 120) {
            return $slug;
        }

        $hashSource = $externalRef !== '' ? $externalRef : $value;
        $hash = substr(sha1($hashSource), 0, 16);

        return mb_substr($slug, 0, 100) . '_' . $hash;
    }

    /**
     * product_lang has a unique (lang, slug) index.
     * 1C may contain different products with identical names, so a plain
     * Str::slug(name) is not sufficient.
     */
    private function uniqueProductSlug(Product $product, string $lang, string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'product-' . $product->id;
        }

        // Leave room for suffixes and stay safely below a typical VARCHAR(255).
        $base = mb_substr($base, 0, 180);
        $slug = $base;

        $exists = static function (string $candidate) use ($product, $lang): bool {
            return DB::table('product_lang')
                ->where('lang', $lang)
                ->where('slug', $candidate)
                ->where('product_id', '<>', $product->id)
                ->exists();
        };

        if (! $exists($slug)) {
            return $slug;
        }

        // Product id makes the suffix deterministic across repeated syncs.
        $slug = $base . '-' . $product->id;

        if (! $exists($slug)) {
            return $slug;
        }

        // Extremely unlikely fallback.
        return mb_substr($base, 0, 160)
            . '-' . $product->id
            . '-' . substr(sha1($product->source . ':' . $product->one_c_id), 0, 8);
    }

    private function source(array $data): string
    {
        return trim((string) ($data['source'] ?? config('onec.source', '1c-unf'))) ?: '1c-unf';
    }
}
