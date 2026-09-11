<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\ProductVariant;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ProductsExport implements FromGenerator, WithHeadings, WithStrictNullComparison
{
    private array $priceTypes = [];
    private array $attributes = [];
    private array $options = [];
    private ?array $categoryMap = null;

    public function __construct(private readonly string $locale = 'ru') {}

    public function headings(): array
    {
        $this->bootMetadata();

        return [
            'ID товара', 'Название товара или услуги', 'Название товара в URL', 'URL',
            'Дополнительное описание', 'Описание', 'Видимость на витрине', 'Применять скидки',
            'Тег title', 'Мета-тег keywords', 'Мета-тег description', 'Размещение на сайте',
            'Весовой коэффициент', 'Валюта склада', 'НДС', 'Единица измерения', 'Габариты',
            'Изображения', 'Ссылка на видео', 'Средний рейтинг', 'Количество отзывов',
            'ID варианта', 'Артикул', 'Штрих-код', 'Внешний ID', 'Габариты варианта',
            'Цена продажи', 'Старая цена', 'Себестоимость', 'Остаток', 'Вес', 'Изображения варианта',
            ...array_map(fn (object $type): string => 'Тип цен: ' . $type->name, $this->priceTypes),
            ...array_map(fn (object $attribute): string => 'Параметр: ' . $attribute->name, $this->attributes),
            ...array_map(fn (object $option): string => 'Опция: ' . $option->name, $this->options),
            'Дополнительное поле: ID 1С',
            'Дополнительное поле: Состав',
        ];
    }

    public function generator(): Generator
    {
        $this->bootMetadata();
        $lastId = 0;

        while (true) {
            $products = DB::table('products')
                ->leftJoin('product_lang', function ($join): void {
                    $join->on('product_lang.product_id', '=', 'products.id')
                        ->where('product_lang.lang', '=', $this->locale);
                })
                ->whereNull('products.deleted_at')
                ->where('products.id', '>', $lastId)
                ->orderBy('products.id')
                ->limit(200)
                ->select([
                    'products.*',
                    'product_lang.name as lang_name',
                    'product_lang.slug as lang_slug',
                    'product_lang.short_description as lang_short_description',
                    'product_lang.description as lang_description',
                    'product_lang.seo_title as lang_seo_title',
                    'product_lang.seo_keywords as lang_seo_keywords',
                    'product_lang.seo_description as lang_seo_description',
                ])->get();

            if ($products->isEmpty()) {
                break;
            }

            $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
            $variants = DB::table('product_variants')
                ->whereIn('product_id', $productIds)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')->orderBy('id')
                ->get()->groupBy('product_id');

            $variantIds = $variants->flatten(1)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $prices = DB::table('product_prices')
                ->whereIn('product_id', $productIds)
                ->get()
                ->groupBy(fn (object $p): string => $p->product_id . ':' . ($p->product_variant_id ?? 0) . ':' . $p->product_price_type_id);

            $productImages = $this->imagesFor((new Product())->getMorphClass(), $productIds);
            $variantImages = $this->imagesFor((new ProductVariant())->getMorphClass(), $variantIds);

            $productAttributeValues = DB::table('product_attribute_values')
                ->join('attribute_values', 'attribute_values.id', '=', 'product_attribute_values.attribute_value_id')
                ->join('attribute_value_lang', function ($join): void {
                    $join->on('attribute_value_lang.attribute_value_id', '=', 'attribute_values.id')
                        ->where('attribute_value_lang.lang', '=', $this->locale);
                })
                ->whereIn('product_attribute_values.product_id', $productIds)
                ->select('product_attribute_values.product_id', 'attribute_values.attribute_id', 'attribute_value_lang.value')
                ->orderBy('product_attribute_values.sort_order')
                ->get()->groupBy('product_id');

            $variantOptionValues = empty($variantIds) ? collect() : DB::table('product_variant_option_values')
                ->join('option_values', 'option_values.id', '=', 'product_variant_option_values.option_value_id')
                ->join('option_value_lang', function ($join): void {
                    $join->on('option_value_lang.option_value_id', '=', 'option_values.id')
                        ->where('option_value_lang.lang', '=', $this->locale);
                })
                ->whereIn('product_variant_option_values.product_variant_id', $variantIds)
                ->select('product_variant_option_values.product_variant_id', 'option_values.option_id', 'option_value_lang.value')
                ->orderBy('product_variant_option_values.sort_order')
                ->get()->groupBy('product_variant_id');

            $categoryAssignments = DB::table('category_product')
                ->whereIn('product_id', $productIds)
                ->orderBy('sort_order')->get()->groupBy('product_id');

            foreach ($products as $product) {
                $rows = $variants->get($product->id, collect());

                if ($rows->isEmpty()) {
                    yield $this->makeRow($product, null, $prices, $productImages, $variantImages, $productAttributeValues, $variantOptionValues, $categoryAssignments);
                    continue;
                }

                foreach ($rows as $variant) {
                    yield $this->makeRow($product, $variant, $prices, $productImages, $variantImages, $productAttributeValues, $variantOptionValues, $categoryAssignments);
                }
            }

            $lastId = (int) $products->last()->id;
        }
    }

    private function makeRow(object $product, ?object $variant, $prices, $productImages, $variantImages, $productAttributeValues, $variantOptionValues, $categoryAssignments): array
    {
        $retailType = $this->findPriceTypeByCode('retail');
        $purchaseType = $this->findPriceTypeByCode('purchase');
        $retail = $retailType ? $this->price($prices, $product->id, $variant?->id, $retailType->id) : null;
        $purchase = $purchaseType ? $this->price($prices, $product->id, $variant?->id, $purchaseType->id) : null;
        $currency = $retail?->currency ?: $purchase?->currency ?: 'RUB';

        $categories = $categoryAssignments->get($product->id, collect())
            ->map(fn (object $pivot): string => $this->categoryPath((int) $pivot->category_id))
            ->filter()->implode(' ## ');

        $attributeMap = $productAttributeValues->get($product->id, collect())
            ->groupBy('attribute_id')
            ->map(fn ($values): string => $values->pluck('value')->filter()->implode(', '));

        $optionMap = $variant
            ? $variantOptionValues->get($variant->id, collect())
                ->groupBy('option_id')
                ->map(fn ($values): string => $values->pluck('value')->filter()->implode(', '))
            : collect();

        $row = [
            $product->external_id ?: $product->id,
            $product->lang_name,
            $product->lang_slug,
            $product->source_url,
            '',
            $product->lang_description,
            $product->is_visible ? 'выставлен' : 'скрыт',
            $product->allow_discounts ? 'да' : 'нет',
            $product->lang_seo_title,
            $product->lang_seo_keywords,
            $product->lang_seo_description,
            $categories,
            '',
            $currency,
            $this->formatPercent($product->vat_rate),
            $product->unit,
            $this->formatDimensions($product->length, $product->width, $product->height),
            $this->imageList($productImages->get($product->id, collect())),
            $product->video_url,
            $product->rating,
            $product->reviews_count,
            $variant?->external_id ?: $variant?->id,
            $variant?->sku ?: $product->sku,
            $variant?->barcode,
            $variant?->external_id,
            $variant ? $this->formatDimensions($variant->length, $variant->width, $variant->height) : '',
            $this->formatDecimal($retail?->amount),
            $this->formatDecimal($retail?->old_amount),
            $this->formatDecimal($purchase?->amount),
            $this->formatDecimal($variant?->stock),
            $this->formatDecimal($variant?->weight),
            $variant ? $this->imageList($variantImages->get($variant->id, collect())) : '',
        ];

        foreach ($this->priceTypes as $type) {
            $row[] = $this->formatDecimal($this->price($prices, $product->id, $variant?->id, $type->id)?->amount);
        }

        foreach ($this->attributes as $attribute) {
            $row[] = $attributeMap->get($attribute->id, '');
        }

        foreach ($this->options as $option) {
            $row[] = $optionMap->get($option->id, '');
        }

        $row[] = $product->one_c_id;
        $row[] = $product->lang_short_description;

        return $row;
    }

    private function imagesFor(string $morphType, array $ids)
    {
        if ($ids === []) return collect();

        return DB::table('images')
            ->where('imageable_type', $morphType)
            ->whereIn('imageable_id', $ids)
            ->orderBy('position')->get()->groupBy('imageable_id');
    }

    private function imageList($images): string
    {
        return $images->pluck('name')->filter()->map(function (string $name): string {
            if (preg_match('~^https?://~i', $name)) return $name;
            return Storage::disk('public')->url($name);
        })->implode(' ');
    }

    private function price($prices, int $productId, ?int $variantId, int $typeId): ?object
    {
        $key = $productId . ':' . ($variantId ?? 0) . ':' . $typeId;
        $found = $prices->get($key)?->first();
        if ($found || $variantId === null) return $found;
        return $prices->get($productId . ':0:' . $typeId)?->first();
    }

    private function bootMetadata(): void
    {
        if ($this->priceTypes === []) {
            $this->priceTypes = DB::table('product_price_types')
                ->where('is_active', true)->whereNotIn('code', ['retail', 'purchase'])
                ->orderBy('sort_order')->orderBy('id')->get()->all();
        }

        if ($this->attributes === []) {
            $this->attributes = DB::table('attributes')
                ->join('attribute_lang', function ($join): void {
                    $join->on('attribute_lang.attribute_id', '=', 'attributes.id')->where('attribute_lang.lang', '=', $this->locale);
                })
                ->orderBy('attributes.sort_order')->orderBy('attributes.id')
                ->select('attributes.id', 'attribute_lang.name')->get()->all();
        }

        if ($this->options === []) {
            $this->options = DB::table('options')
                ->join('option_lang', function ($join): void {
                    $join->on('option_lang.option_id', '=', 'options.id')->where('option_lang.lang', '=', $this->locale);
                })
                ->where('options.is_active', true)
                ->orderBy('options.sort_order')->orderBy('options.id')
                ->select('options.id', 'option_lang.name')->get()->all();
        }
    }

    private function findPriceTypeByCode(string $code): ?object
    {
        static $cache = [];
        return $cache[$code] ??= DB::table('product_price_types')->where('code', $code)->first();
    }

    private function categoryPath(int $categoryId): string
    {
        $map = $this->categoryMap();
        $names = [];
        $guard = 0;
        while ($categoryId && isset($map[$categoryId]) && $guard++ < 50) {
            $node = $map[$categoryId];
            array_unshift($names, $node['name']);
            $categoryId = (int) ($node['parent_id'] ?? 0);
        }
        return implode('/', array_filter($names));
    }

    private function categoryMap(): array
    {
        if ($this->categoryMap !== null) return $this->categoryMap;

        return $this->categoryMap = DB::table('categories')
            ->leftJoin('category_lang', function ($join): void {
                $join->on('category_lang.category_id', '=', 'categories.id')->where('category_lang.lang', '=', $this->locale);
            })
            ->select('categories.id', 'categories.parent_id', 'category_lang.name')
            ->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => [
                'parent_id' => $row->parent_id,
                'name' => $row->name ?: ('#' . $row->id),
            ]])->all();
    }

    private function formatDimensions(mixed $length, mixed $width, mixed $height): string
    {
        if ($length === null || $width === null || $height === null) return '';
        return $this->number($length) . 'x' . $this->number($width) . 'x' . $this->number($height);
    }

    private function formatPercent(mixed $value): string
    {
        return $value === null ? '' : $this->number($value) . '%';
    }

    private function formatDecimal(mixed $value): string|float
    {
        return ($value === null || $value === '') ? '' : (float) $value;
    }

    private function number(mixed $value): string
    {
        $number = number_format((float) $value, 3, '.', '');
        return rtrim(rtrim($number, '0'), '.');
    }
}
