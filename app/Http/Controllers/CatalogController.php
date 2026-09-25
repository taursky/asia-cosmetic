<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $categoryIds = $this->categoryIdsWithActiveProducts();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->whereIn('id', $categoryIds)
            ->with([
                'lang',
                'children' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('id', $categoryIds)
                    ->orderBy('sort_order'),
                'children.lang',
            ])
            ->orderBy('sort_order')
            ->get();

        $products = $this->productListingQuery($locale)
            ->latest('products.created_at')
            ->paginate(24)
            ->withQueryString();

        return view('catalog.index', compact(
            'categories',
            'products',
        ));
    }

    public function category(Request $request, string $categorySlug): View
    {
        $locale = app()->getLocale();
        $visibleCategoryIds = $this->categoryIdsWithActiveProducts();

        $category = Category::query()
            ->where('is_active', true)
            ->whereIn('categories.id', $visibleCategoryIds)
            ->whereHas('langs', fn (Builder $query) => $query
                ->where('lang', $locale)
                ->where('slug', $categorySlug))
            ->with([
                'lang',
                'parent.lang',
                'children' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('id', $visibleCategoryIds)
                    ->orderBy('sort_order'),
                'children.lang',
            ])
            ->firstOrFail();

        $categoryIds = $this->categoryTreeIds($category);

        $products = $this->productListingQuery($locale)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query
                    ->whereIn('categories.id', $categoryIds)
                    ->orderBy('categories.sort_order');
            })
            ->latest('products.created_at')
            ->paginate(24)
            ->withQueryString();

        return view('catalog.category', compact(
            'category',
            'products',
        ));
    }

    public function product(string $productSlug): View
    {
        $locale = app()->getLocale();

        $product = Product::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->whereHas('langs', fn (Builder $query) => $query
                ->where('lang', $locale)
                ->where('slug', $productSlug))
            ->with([
                'lang',
                'langs',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position'),

                'categories.lang',

                'attributeValues.attribute.lang',
                'attributeValues.lang',

                'prices' => fn ($query) => $query
                    ->with('priceType')
                    ->whereNull('product_variant_id')
                    ->orderBy('min_quantity'),

                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),

                'variants.lang',
                'variants.images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position'),

                'variants.prices' => fn ($query) => $query
                    ->with('priceType')
                    ->orderBy('min_quantity'),

                'variants.optionValues.option.lang',
                'variants.optionValues.lang',
            ])
            ->firstOrFail();

        $relatedProducts = $this->relatedProducts($product, $locale);

        return view('catalog.product', compact(
            'product',
            'relatedProducts',
        ));
    }

    private function productListingQuery(string $locale): Builder
    {
        return Product::query()
            ->select('products.*')
            ->where('products.is_active', true)
            ->where('products.is_visible', true)
            ->whereHas('langs', fn (Builder $query) => $query
                ->where('lang', $locale))
            ->with([
                'lang',

                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position'),

                'prices' => fn ($query) => $query
                    ->whereNull('product_variant_id')
                    ->with('priceType')
                    ->orderBy('min_quantity'),

                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),

                'variants.images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('position'),

                'variants.prices' => fn ($query) => $query
                    ->with('priceType')
                    ->orderBy('min_quantity'),
            ]);
    }

    /**
     * Категории, в которых есть активные/видимые товары, плюс их родители.
     * Это позволяет показывать родительский раздел каталога, даже если товары
     * находятся только в его дочерних категориях.
     */
    private function categoryIdsWithActiveProducts(): array
    {
        $directIds = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->where('products.is_active', true)
            ->where('products.is_visible', true)
            ->whereNull('products.deleted_at')
            ->distinct()
            ->pluck('category_product.category_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($directIds === []) {
            return [];
        }

        $parents = Category::query()
            ->where('is_active', true)
            ->pluck('parent_id', 'id');

        $result = array_fill_keys($directIds, true);

        foreach ($directIds as $categoryId) {
            $currentId = $categoryId;
            $guard = 0;

            while ($guard++ < 100) {
                $parentId = $parents->get($currentId);

                if (! $parentId) {
                    break;
                }

                $parentId = (int) $parentId;
                $result[$parentId] = true;
                $currentId = $parentId;
            }
        }

        return array_map('intval', array_keys($result));
    }

    private function categoryTreeIds(Category $category): array
    {
        $ids = [$category->id];

        $children = Category::query()
            ->where('is_active', true)
            ->where('parent_id', $category->id)
            ->pluck('id')
            ->all();

        while ($children !== []) {
            $ids = array_merge($ids, $children);

            $children = Category::query()
                ->where('is_active', true)
                ->whereIn('parent_id', $children)
                ->pluck('id')
                ->all();
        }

        return array_values(array_unique($ids));
    }

    private function relatedProducts(Product $product, string $locale)
    {
        $categoryIds = $product->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return collect();
        }

        return $this->productListingQuery($locale)
            ->whereKeyNot($product->getKey())
            ->whereHas('categories', fn (Builder $query) => $query
                ->whereIn('categories.id', $categoryIds))
            ->limit(8)
            ->get();
    }
}
