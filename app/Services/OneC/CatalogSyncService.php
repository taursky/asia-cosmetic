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
                $result->errors[] = ['index' => $index, 'ref' => $data['ref'] ?? null, 'message' => $e->getMessage()];
            }
        }
        return $result;
    }

    private function syncProduct(array $data, SyncResult $result): void
    {
        $locale = (string) config('onec.locale', 'ru');
        $oneCId = $data['ref'] ?? $data['one_c_id'] ?? null;
        if (! $oneCId) throw new \InvalidArgumentException('Product ref is required.');

        $product = Product::withTrashed()->firstOrNew(['one_c_id' => $oneCId]);
        $exists = $product->exists;
        $product->fill([
            'external_id' => $data['external_id'] ?? $product->external_id,
            'sku' => $data['sku'] ?? $product->sku,
            'source' => config('onec.source', '1c-unf'),
            'is_active' => (bool) ($data['active'] ?? true),
            'vat_rate' => $data['vat_rate'] ?? $product->vat_rate,
            'unit' => $data['unit'] ?? $product->unit,
            'weight' => $data['weight'] ?? $product->weight,
            'length' => $data['length'] ?? $product->length,
            'width' => $data['width'] ?? $product->width,
            'height' => $data['height'] ?? $product->height,
            'synced_at' => now(),
        ])->save();
        if (method_exists($product, 'trashed') && $product->trashed()) $product->restore();

        if (! empty($data['name'])) {
            $lang = $data['lang'] ?? $locale;
            $product->langs()->updateOrCreate(['lang' => $lang], [
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
        }

        $this->syncCategories($product, $data['categories'] ?? []);
        $this->syncAttributes($product, $data['attributes'] ?? [], $locale);
        $this->syncVariants($product, $data['variants'] ?? [], $locale);

        $exists ? $result->updated++ : $result->created++;
    }

    private function syncCategories(Product $product, array $categories): void
    {
        $ids = collect($categories)
            ->pluck('ref')
            ->filter()
            ->map(fn ($ref) => Category::query()->where('one_c_id', $ref)->value('id'))
            ->filter()
            ->values()
            ->all();
        if ($ids !== []) $product->categories()->sync($ids);
    }

    private function syncAttributes(Product $product, array $attributes, string $locale): void
    {
        $valueIds = [];
        foreach ($attributes as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($name === '' || $value === '') continue;

            $attribute = $this->findOrCreateAttribute($row, $name, $locale);
            $attributeValue = $this->findOrCreateAttributeValue($attribute, $row, $value, $locale);
            $valueIds[] = $attributeValue->id;
        }
        if ($valueIds !== []) $product->attributeValues()->sync($valueIds);
    }

    private function findOrCreateAttribute(array $row, string $name, string $locale): Attribute
    {
        $attribute = null;
        if (! empty($row['attribute_ref'])) $attribute = Attribute::query()->where('one_c_id', $row['attribute_ref'])->first();
        $attribute ??= Attribute::query()->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('name', $name))->first();
        if (! $attribute) {
            $attribute = Attribute::create([
                'code' => Str::slug($name, '_') ?: 'attr_' . Str::lower(Str::random(8)),
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
        if (! empty($row['value_ref'])) $model = AttributeValue::query()->where('one_c_id', $row['value_ref'])->first();
        $model ??= $attribute->values()->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('value', $value))->first();
        if (! $model) {
            $model = $attribute->values()->create([
                'code' => Str::slug($value, '_') ?: 'value_' . Str::lower(Str::random(8)),
                'one_c_id' => $row['value_ref'] ?? null,
            ]);
            $model->langs()->create(['lang' => $locale, 'value' => $value]);
        }
        return $model;
    }

    private function syncVariants(Product $product, array $variants, string $locale): void
    {
        foreach ($variants as $row) {
            $ref = $row['ref'] ?? $row['one_c_id'] ?? null;
            if (! $ref) continue;

            $variant = ProductVariant::withTrashed()->firstOrNew(['one_c_id' => $ref]);
            $variant->fill([
                'product_id' => $product->id,
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
            if (method_exists($variant, 'trashed') && $variant->trashed()) $variant->restore();

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
            if ($name === '' || $value === '') continue;

            $option = null;
            if (! empty($row['option_ref'])) $option = Option::query()->where('one_c_id', $row['option_ref'])->first();
            $option ??= Option::query()->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('name', $name))->first();
            if (! $option) {
                $option = Option::create([
                    'code' => Str::slug($name, '_') ?: 'option_' . Str::lower(Str::random(8)),
                    'one_c_id' => $row['option_ref'] ?? null,
                ]);
                $option->langs()->create(['lang' => $locale, 'name' => $name]);
            }

            $optionValue = null;
            if (! empty($row['value_ref'])) $optionValue = OptionValue::query()->where('one_c_id', $row['value_ref'])->first();
            $optionValue ??= $option->values()->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('value', $value))->first();
            if (! $optionValue) {
                $optionValue = $option->values()->create([
                    'code' => Str::slug($value, '_') ?: 'value_' . Str::lower(Str::random(8)),
                    'one_c_id' => $row['value_ref'] ?? null,
                ]);
                $optionValue->langs()->create(['lang' => $locale, 'value' => $value]);
            }
            $valueIds[] = $optionValue->id;
        }

        if ($valueIds !== []) {
            $variant->optionValues()->sync($valueIds);
            $product->optionValues()->syncWithoutDetaching($valueIds);
        }
    }
}
