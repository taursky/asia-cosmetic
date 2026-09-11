<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductImportService
{
    private array $stats = [
        'rows' => 0,
        'products_created' => 0,
        'products_updated' => 0,
        'variants_created' => 0,
        'variants_updated' => 0,
        'attributes_created' => 0,
        'attribute_values_created' => 0,
        'options_created' => 0,
        'option_values_created' => 0,
        'prices_written' => 0,
        'categories_created' => 0,
        'images_written' => 0,
        'warnings' => [],
    ];

    public function __construct(
        private readonly string $locale = 'ru',
        private readonly string $source = 'insales',
    ) {}

    public function importRow(array $row): void
    {
        if ($this->isEmptyRow($row)) {
            return;
        }

        ++$this->stats['rows'];

        [$product, $variant] = DB::transaction(function () use ($row): array {
            $product = $this->upsertProduct($row);
            $variant = $this->upsertVariant($product, $row);

            $this->upsertProductLang($product, $row);
            $this->syncPrices($product, $variant, $row);
            $this->syncAttributes($product, $row);
            $this->syncOptions($product, $variant, $row);
            $this->syncCategories($product, $row);

            return [$product, $variant];
        });

        // Network I/O must not keep the DB transaction open.
        $this->syncImages($product, $variant, $row);
    }

    public function stats(): array
    {
        return $this->stats;
    }

    private function upsertProduct(array $row): Product
    {
        $oneCId = $this->nullableString($row['Дополнительное поле: ID 1С'] ?? null);
        $externalId = $this->nullableString($row['ID товара'] ?? null);

        $product = null;

        if ($oneCId) {
            $product = Product::withTrashed()->where('one_c_id', $oneCId)->first();
        }

        if (! $product && $externalId) {
            $product = Product::withTrashed()
                ->where('source', $this->source)
                ->where('external_id', $externalId)
                ->first();
        }

        $isNew = ! $product;
        $product ??= new Product();
        $dimensions = $this->parseDimensions($row['Габариты'] ?? null);

        $data = [
            'external_id' => $externalId,
            'one_c_id' => $oneCId,
            'source' => $this->source,
            'source_url' => $this->nullableString($row['URL'] ?? null),
            'is_active' => true,
            'is_visible' => $this->parseVisibility($row['Видимость на витрине'] ?? null),
            'allow_discounts' => $this->parseYesNo($row['Применять скидки'] ?? null, true),
            'vat_rate' => $this->parsePercent($row['НДС'] ?? null),
            'unit' => $this->nullableString($row['Единица измерения'] ?? null),
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'video_url' => $this->nullableString($row['Ссылка на видео'] ?? null),
            'rating' => $this->decimal($row['Средний рейтинг'] ?? null),
            'reviews_count' => (int) ($this->decimal($row['Количество отзывов'] ?? null) ?? 0),
            'synced_at' => now(),
        ];

        if (! $product->sku) {
            $data['sku'] = $this->nullableString($row['Артикул'] ?? null);
        }

        $product->fill($data)->save();

        if ($product->trashed()) {
            $product->restore();
        }

        ++$this->stats[$isNew ? 'products_created' : 'products_updated'];

        return $product;
    }

    private function upsertVariant(Product $product, array $row): ProductVariant
    {
        $externalId = $this->nullableString($row['Внешний ID'] ?? null)
            ?: $this->nullableString($row['ID варианта'] ?? null);
        $sku = $this->nullableString($row['Артикул'] ?? null);

        $variant = null;

        if ($externalId) {
            $variant = ProductVariant::withTrashed()
                ->where('product_id', $product->getKey())
                ->where('external_id', $externalId)
                ->first();
        }

        if (! $variant && $sku) {
            $variant = ProductVariant::withTrashed()
                ->where('product_id', $product->getKey())
                ->where('sku', $sku)
                ->first();
        }

        $isNew = ! $variant;
        $variant ??= new ProductVariant(['product_id' => $product->getKey()]);
        $dimensions = $this->parseDimensions($row['Габариты варианта'] ?? null);

        $variant->fill([
            'product_id' => $product->getKey(),
            'external_id' => $externalId,
            'sku' => $sku,
            'barcode' => $this->nullableString($row['Штрих-код'] ?? null),
            'is_active' => true,
            'stock' => $this->decimal($row['Остаток'] ?? null) ?? 0,
            'weight' => $this->decimal($row['Вес'] ?? null),
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'synced_at' => now(),
        ])->save();

        if ($variant->trashed()) {
            $variant->restore();
        }

        ++$this->stats[$isNew ? 'variants_created' : 'variants_updated'];

        return $variant;
    }

    private function upsertProductLang(Product $product, array $row): void
    {
        $name = $this->nullableString($row['Название товара или услуги'] ?? null);

        if (! $name) {
            throw new RuntimeException('Не заполнено поле "Название товара или услуги".');
        }

        $slug = $this->nullableString($row['Название товара в URL'] ?? null) ?: Str::slug($name);
        $slug = $this->uniqueProductSlug($slug, $product->getKey());

        $this->updateOrInsertWithTimestamps('product_lang', [
            'product_id' => $product->getKey(),
            'lang' => $this->locale,
        ], [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $this->nullableString($row['Дополнительное поле: Состав'] ?? null),
            'description' => $this->nullableString($row['Описание'] ?? null),
            'seo_title' => $this->nullableString($row['Тег title'] ?? null),
            'seo_keywords' => $this->nullableString($row['Мета-тег keywords'] ?? null),
            'seo_description' => $this->nullableString($row['Мета-тег description'] ?? null),
        ]);
    }

    private function syncPrices(Product $product, ProductVariant $variant, array $row): void
    {
        $currency = Str::limit(strtoupper($this->nullableString($row['Валюта склада'] ?? null) ?: 'RUB'), 3, '');

        $retail = $this->decimal($row['Цена продажи'] ?? null);
        if ($retail !== null) {
            $this->writePrice($product, $variant, $this->priceType('retail', 'Розничная'), $retail, $this->decimal($row['Старая цена'] ?? null), $currency);
        }

        $purchase = $this->decimal($row['Себестоимость'] ?? null);
        if ($purchase !== null) {
            $this->writePrice($product, $variant, $this->priceType('purchase', 'Закупочная'), $purchase, null, $currency);
        }

        foreach ($row as $heading => $rawValue) {
            if (! str_starts_with((string) $heading, 'Тип цен:')) {
                continue;
            }

            $amount = $this->decimal($rawValue);
            $name = trim(Str::after((string) $heading, 'Тип цен:'));

            if ($amount === null || $name === '') {
                continue;
            }

            $code = Str::slug($name, '_') ?: 'price_' . substr(sha1($name), 0, 10);
            $this->writePrice($product, $variant, $this->priceType($code, $name), $amount, null, $currency);
        }
    }

    private function writePrice(Product $product, ProductVariant $variant, int $priceTypeId, float $amount, ?float $oldAmount, string $currency): void
    {
        $this->updateOrInsertWithTimestamps('product_prices', [
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'product_price_type_id' => $priceTypeId,
        ], [
            'amount' => $amount,
            'old_amount' => $oldAmount,
            'currency' => $currency,
            'min_quantity' => 1,
            'synced_at' => now(),
        ]);

        ++$this->stats['prices_written'];
    }

    private function priceType(string $code, string $name): int
    {
        $existing = DB::table('product_price_types')->where('code', $code)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('product_price_types')->insertGetId([
            'code' => $code,
            'name' => $name,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function syncAttributes(Product $product, array $row): void
    {
        $position = 0;

        foreach ($row as $heading => $rawValue) {
            if (! str_starts_with((string) $heading, 'Параметр:')) {
                continue;
            }

            $value = $this->nullableString($rawValue);
            $name = trim(Str::after((string) $heading, 'Параметр:'));

            if ($value === null || $name === '') {
                continue;
            }

            $attributeId = $this->findOrCreateAttribute($name);
            $attributeValueId = $this->findOrCreateAttributeValue($attributeId, $value);

            DB::table('product_attribute_values')->updateOrInsert([
                'product_id' => $product->getKey(),
                'attribute_value_id' => $attributeValueId,
            ], ['sort_order' => $position++]);
        }
    }

    /** Future-compatible: columns `Опция: Цвет`, `Опция: Объем` describe SKU choices. */
    private function syncOptions(Product $product, ProductVariant $variant, array $row): void
    {
        $position = 0;

        foreach ($row as $heading => $rawValue) {
            if (! str_starts_with((string) $heading, 'Опция:')) {
                continue;
            }

            $value = $this->nullableString($rawValue);
            $name = trim(Str::after((string) $heading, 'Опция:'));

            if ($value === null || $name === '') {
                continue;
            }

            $optionId = $this->findOrCreateOption($name);
            $optionValueId = $this->findOrCreateOptionValue($optionId, $value);

            DB::table('product_option_values')->updateOrInsert([
                'product_id' => $product->getKey(),
                'option_value_id' => $optionValueId,
            ], ['sort_order' => $position]);

            DB::table('product_variant_option_values')->updateOrInsert([
                'product_variant_id' => $variant->getKey(),
                'option_value_id' => $optionValueId,
            ], ['sort_order' => $position++]);
        }
    }

    private function findOrCreateAttribute(string $name): int
    {
        $id = DB::table('attributes')
            ->join('attribute_lang', 'attribute_lang.attribute_id', '=', 'attributes.id')
            ->where('attribute_lang.lang', $this->locale)
            ->where('attribute_lang.name', $name)
            ->value('attributes.id');

        if ($id) {
            return (int) $id;
        }

        $code = $this->uniqueCode('attributes', Str::slug($name, '_') ?: 'attribute_' . substr(sha1($name), 0, 10));
        $id = (int) DB::table('attributes')->insertGetId([
            'code' => $code,
            'type' => $this->guessAttributeType($name),
            'is_filterable' => $this->isFilterableAttribute($name),
            'is_variant' => false,
            'is_searchable' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_lang')->insert([
            'attribute_id' => $id,
            'lang' => $this->locale,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ++$this->stats['attributes_created'];
        return $id;
    }

    private function findOrCreateAttributeValue(int $attributeId, string $value): int
    {
        $id = DB::table('attribute_values')
            ->join('attribute_value_lang', 'attribute_value_lang.attribute_value_id', '=', 'attribute_values.id')
            ->where('attribute_values.attribute_id', $attributeId)
            ->where('attribute_value_lang.lang', $this->locale)
            ->where('attribute_value_lang.value', $value)
            ->value('attribute_values.id');

        if ($id) {
            return (int) $id;
        }

        $base = mb_strlen($value) <= 120 ? (Str::slug($value, '_') ?: 'value_' . substr(sha1($value), 0, 12)) : 'value_' . substr(sha1($value), 0, 20);
        $code = $this->uniqueScopedCode('attribute_values', 'attribute_id', $attributeId, $base);
        $id = (int) DB::table('attribute_values')->insertGetId([
            'attribute_id' => $attributeId,
            'code' => $code,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_value_lang')->insert([
            'attribute_value_id' => $id,
            'lang' => $this->locale,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ++$this->stats['attribute_values_created'];
        return $id;
    }

    private function findOrCreateOption(string $name): int
    {
        $id = DB::table('options')
            ->join('option_lang', 'option_lang.option_id', '=', 'options.id')
            ->where('option_lang.lang', $this->locale)
            ->where('option_lang.name', $name)
            ->value('options.id');

        if ($id) {
            return (int) $id;
        }

        $code = $this->uniqueCode('options', Str::slug($name, '_') ?: 'option_' . substr(sha1($name), 0, 10));
        $id = (int) DB::table('options')->insertGetId([
            'code' => $code,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('option_lang')->insert([
            'option_id' => $id,
            'lang' => $this->locale,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ++$this->stats['options_created'];
        return $id;
    }

    private function findOrCreateOptionValue(int $optionId, string $value): int
    {
        $id = DB::table('option_values')
            ->join('option_value_lang', 'option_value_lang.option_value_id', '=', 'option_values.id')
            ->where('option_values.option_id', $optionId)
            ->where('option_value_lang.lang', $this->locale)
            ->where('option_value_lang.value', $value)
            ->value('option_values.id');

        if ($id) {
            return (int) $id;
        }

        $base = Str::slug($value, '_') ?: 'value_' . substr(sha1($value), 0, 12);
        $code = $this->uniqueScopedCode('option_values', 'option_id', $optionId, $base);
        $id = (int) DB::table('option_values')->insertGetId([
            'option_id' => $optionId,
            'code' => $code,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('option_value_lang')->insert([
            'option_value_id' => $id,
            'lang' => $this->locale,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ++$this->stats['option_values_created'];
        return $id;
    }

    private function syncCategories(Product $product, array $row): void
    {
        $raw = $this->nullableString($row['Размещение на сайте'] ?? null);
        if (! $raw) {
            return;
        }

        $paths = preg_split('/\s*##\s*/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $position = 0;

        foreach ($paths as $path) {
            $parentId = null;
            $parts = array_values(array_filter(array_map('trim', explode('/', $path))));
            $slugParts = [];

            foreach ($parts as $part) {
                $slugParts[] = Str::slug($part);
                $parentId = $this->findOrCreateCategory($part, $parentId, implode('/', $slugParts));
            }

            if ($parentId) {
                DB::table('category_product')->updateOrInsert(
                    ['category_id' => $parentId, 'product_id' => $product->getKey()],
                    ['sort_order' => $position++],
                );
            }
        }
    }

    private function findOrCreateCategory(string $name, ?int $parentId, string $path): int
    {
        $query = DB::table('categories')
            ->join('category_lang', 'category_lang.category_id', '=', 'categories.id')
            ->where('category_lang.lang', $this->locale)
            ->where('category_lang.name', $name);
        $parentId === null ? $query->whereNull('categories.parent_id') : $query->where('categories.parent_id', $parentId);

        $id = $query->value('categories.id');
        if ($id) {
            return (int) $id;
        }

        $id = (int) DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slug = $this->uniqueLangSlug('category_lang', Str::slug(str_replace('/', '-', $path)) ?: 'category-' . $id);
        DB::table('category_lang')->insert([
            'category_id' => $id,
            'lang' => $this->locale,
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ++$this->stats['categories_created'];
        return $id;
    }

    private function syncImages(Product $product, ProductVariant $variant, array $row): void
    {
        $this->writeImages($product, 'products/' . $product->getKey(), $row['Изображения'] ?? null);
        $this->writeImages($variant, 'products/' . $product->getKey() . '/variants/' . $variant->getKey(), $row['Изображения варианта'] ?? null);
    }

    private function writeImages(Product|ProductVariant $model, string $directory, mixed $raw): void
    {
        $raw = $this->nullableString($raw);
        if (! $raw) {
            return;
        }

        $urls = preg_split('/\s+(?=https?:\/\/)/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $disk = Storage::disk('public');
        $morphType = $model->getMorphClass();

        foreach ($urls as $index => $url) {
            $url = trim($url);

            try {
                $response = Http::timeout(20)->retry(2, 300)->get($url);
                if (! $response->successful()) {
                    throw new RuntimeException('HTTP ' . $response->status());
                }

                $body = $response->body();
                $mime = $this->normalizeImageMime($response->header('Content-Type')) ?: $this->mimeFromUrl($url);
                $extension = $this->extensionFromMime($mime) ?: pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'jpg');

                $name = 'catalog/' . trim($directory, '/') . '/' . sha1($url) . '.' . $extension;
                if (! $disk->exists($name)) {
                    $disk->put($name, $body, ['visibility' => 'public']);
                }

                $exists = DB::table('images')
                    ->where('imageable_type', $morphType)
                    ->where('imageable_id', $model->getKey())
                    ->where('name', $name)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('images')->insert([
                    'position' => $index + 1,
                    'imageable_id' => $model->getKey(),
                    'imageable_type' => $morphType,
                    'name' => $name,
                    'mime_type' => $mime,
                    'is_primary' => $index === 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                ++$this->stats['images_written'];
            } catch (Throwable $e) {
                $this->stats['warnings'][] = "Не удалось скачать изображение {$url}: {$e->getMessage()}";
            }
        }
    }

    private function normalizeImageMime(?string $contentType): ?string
    {
        $mime = strtolower(trim(explode(';', (string) $contentType)[0] ?? ''));
        return str_starts_with($mime, 'image/') ? $mime : null;
    }

    private function extensionFromMime(?string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => null,
        };
    }

    private function mimeFromUrl(string $url): ?string
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            default => null,
        };
    }

    private function uniqueProductSlug(string $slug, int $productId): string
    {
        $base = $slug ?: 'product-' . $productId;
        $candidate = $base;
        $suffix = 2;

        while (DB::table('product_lang')->where('lang', $this->locale)->where('slug', $candidate)->where('product_id', '!=', $productId)->exists()) {
            $candidate = $base . '-' . $suffix++;
        }
        return $candidate;
    }

    private function uniqueLangSlug(string $table, string $slug): string
    {
        $candidate = $slug;
        $suffix = 2;
        while (DB::table($table)->where('lang', $this->locale)->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $suffix++;
        }
        return $candidate;
    }

    private function uniqueCode(string $table, string $base): string
    {
        $candidate = Str::limit($base, 240, '');
        $suffix = 2;
        while (DB::table($table)->where('code', $candidate)->exists()) {
            $candidate = Str::limit($base, 230, '') . '_' . $suffix++;
        }
        return $candidate;
    }

    private function uniqueScopedCode(string $table, string $scopeColumn, int $scopeId, string $base): string
    {
        $candidate = Str::limit($base, 240, '');
        $suffix = 2;
        while (DB::table($table)->where($scopeColumn, $scopeId)->where('code', $candidate)->exists()) {
            $candidate = Str::limit($base, 230, '') . '_' . $suffix++;
        }
        return $candidate;
    }

    private function guessAttributeType(string $name): string
    {
        $normalized = mb_strtolower($name);
        return match (true) {
            str_contains($normalized, 'объем'), str_contains($normalized, 'объём'),
            str_contains($normalized, 'состав'), str_contains($normalized, 'способ применения') => 'text',
            default => 'select',
        };
    }

    private function isFilterableAttribute(string $name): bool
    {
        $normalized = mb_strtolower($name);
        return ! str_contains($normalized, 'состав') && ! str_contains($normalized, 'способ применения');
    }

    private function parseDimensions(mixed $value): array
    {
        $empty = ['length' => null, 'width' => null, 'height' => null];
        $value = $this->nullableString($value);
        if (! $value) return $empty;

        $parts = preg_split('/\s*[xх×]\s*/ui', $value) ?: [];
        if (count($parts) < 3) return $empty;

        return ['length' => $this->decimal($parts[0]), 'width' => $this->decimal($parts[1]), 'height' => $this->decimal($parts[2])];
    }

    private function parseVisibility(mixed $value): bool
    {
        return ! in_array(mb_strtolower(trim((string) $value)), ['скрыт', 'скрыто', 'нет', '0', 'false'], true);
    }

    private function parseYesNo(mixed $value, bool $default = false): bool
    {
        $value = $this->nullableString($value);
        return $value === null ? $default : in_array(mb_strtolower($value), ['да', 'yes', '1', 'true', 'on'], true);
    }

    private function parsePercent(mixed $value): ?float
    {
        $value = $this->nullableString($value);
        return $value === null ? null : $this->decimal(str_replace('%', '', $value));
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        $value = str_replace(["\xC2\xA0", ' '], '', trim((string) $value));
        $value = str_replace(',', '.', $value);
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->nullableString($value) !== null) return false;
        }
        return true;
    }

    private function updateOrInsertWithTimestamps(string $table, array $keys, array $values): void
    {
        $query = DB::table($table);
        foreach ($keys as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        if ($query->exists()) {
            $query->update([...$values, 'updated_at' => now()]);
            return;
        }

        DB::table($table)->insert([...$keys, ...$values, 'created_at' => now(), 'updated_at' => now()]);
    }
}
