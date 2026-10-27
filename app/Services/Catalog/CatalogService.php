<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\CatalogCollection;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\CatalogPricePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CatalogService
{
    public function __construct(private readonly CatalogPricePresenter $prices) {}

    public function listingQuery(string $locale, ?User $user = null): Builder
    {
        $query = Product::query()
            ->select('products.*')
            ->where('products.is_active', true)
            ->where('products.is_visible', true)
            ->whereHas('langs', fn (Builder $q) => $q->where('lang', $locale))
            ->with($this->listingRelations());

        // Favorited state is fetched as a subquery, not one query per card.
        if ($user !== null) {
            $query->withExists([
                'favoritedBy as is_favorite' => fn (Builder $q) => $q->where('users.id', $user->getKey()),
            ]);
        }

        return $query;
    }

    public function listingRelations(): array
    {
        return [
            'lang',
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position'),
            'prices' => fn ($q) => $q->whereNull('product_variant_id')->with('priceType')->orderBy('min_quantity'),
            'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'variants.images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position'),
            'variants.prices' => fn ($q) => $q->with('priceType')->orderBy('min_quantity'),
        ];
    }

    public function detailQuery(string $locale, ?User $user = null): Builder
    {
        return $this->listingQuery($locale, $user)->with([
            'langs', 'categories.lang', 'attributeValues.attribute.lang', 'attributeValues.lang',
            'variants.lang', 'variants.optionValues.option.lang', 'variants.optionValues.lang',
        ]);
    }

    public function applyPreset(Builder $query, array $preset): Builder
    {
        $filter = $preset['filter'] ?? 'all';
        if ($filter === 'sale') {
            $query->where(function (Builder $q): void {
                $discount = static fn (Builder $prices) => $prices
                    ->whereNotNull('old_amount')
                    ->whereColumn('old_amount', '>', 'amount')
                    ->where('amount', '>', 0)
                    ->where('min_quantity', '<=', 1)
                    ->where(fn (Builder $d) => $d->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
                    ->where(fn (Builder $d) => $d->whereNull('valid_until')->orWhere('valid_until', '>=', now()));
                $q->whereHas('prices', $discount)
                    ->orWhereHas('variants', fn (Builder $v) => $v->where('is_active', true)->whereHas('prices', $discount));
            });
        } elseif ($filter === 'rated') {
            $query->where('products.reviews_count', '>', 0);
        } elseif ($filter !== 'all') {
            abort(404);
        }

        return $this->sort($query, $preset['sort'] ?? 'new');
    }

    public function applyCollection(Builder $query, CatalogCollection $collection): Builder
    {
        $filter = $collection->filter;
        if ($filter === 'manual') {
            $query->whereHas('catalogCollections', fn (Builder $q) => $q->whereKey($collection->id));
        } elseif ($filter === 'new') {
            $query->where(function ($subQuery) {
                $subQuery->where('products.updated_at', '>=', now()->subDays(30))
                    ->orWhere('badge_new_until', '>=', now());
            });
        } elseif ($filter === 'hit') {
            $query->where('products.is_hit',true);
        } else {
            $this->applyPreset($query, ['filter' => $filter, 'sort' => $collection->sort]);
        }
        //todo  add

        if ($collection->category_id !== null) {
            $category = Category::query()->find($collection->category_id);
            if ($category === null || ! $category->is_active) {
                $query->whereRaw('1 = 0');
            } else {
                $ids = $this->categoryTreeIds($category);
                $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $ids));
            }
        }

        // The preset branch has already sorted the query; avoid duplicate ORDER BY.
        return in_array($filter, ['manual', 'new'], true)
            ? $this->sort($query, $collection->sort)
            : $query;
    }

    public function sort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'popular' => $query->orderByDesc('products.reviews_count')->orderByDesc('products.rating')->orderByDesc('products.id'),
            'rating' => $query->orderByDesc('products.rating')->orderByDesc('products.reviews_count')->orderByDesc('products.id'),
            'old' => $query->orderBy('products.created_at')->orderBy('products.id'),
            default => $query->orderByDesc('products.created_at')->orderByDesc('products.id'),
        };
    }

    public function paginate(Builder $query, ?User $user, int $perPage = 24): LengthAwarePaginator
    {
        $paginator = $query->paginate($perPage)->withQueryString();
        $this->prices->decoratePaginator($paginator, $user);
        return $paginator;
    }

    public function decorateProduct(Product $product, ?User $user): Product
    {
        return $this->prices->decorateProduct($product, $user);
    }

    public function decorateCollection(EloquentCollection $products, ?User $user): EloquentCollection
    {
        $this->prices->decorateCollection($products, $user);
        return $products;
    }

    public function categoryIdsWithActiveProducts(): array
    {
        $directIds = DB::table('category_product')
            ->join('products', 'products.id', '=', 'category_product.product_id')
            ->where('products.is_active', true)->where('products.is_visible', true)
            ->whereNull('products.deleted_at')->distinct()
            ->pluck('category_product.category_id')->map(fn ($id) => (int) $id)->all();

        if ($directIds === []) return [];

        $parents = Category::query()->where('is_active', true)->pluck('parent_id', 'id');
        $result = array_fill_keys($directIds, true);
        foreach ($directIds as $categoryId) {
            $currentId = $categoryId;
            $seen = [];
            while (! isset($seen[$currentId])) {
                $seen[$currentId] = true;
                $parentId = $parents->get($currentId);
                if (! $parentId) break;
                $currentId = (int) $parentId;
                $result[$currentId] = true;
            }
        }
        return array_map('intval', array_keys($result));
    }

    public function categoryTreeIds(Category $category): array
    {
        $ids = [(int) $category->id];
        $frontier = $ids;
        while ($frontier !== []) {
            $children = Category::query()->where('is_active', true)
                ->whereIn('parent_id', $frontier)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $frontier = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $frontier);
        }
        return $ids;
    }

    public function relatedProducts(Product $product, string $locale, ?User $user = null): EloquentCollection
    {
        $categoryIds = $product->categories->pluck('id');
        if ($categoryIds->isEmpty()) return new EloquentCollection();

        return $this->listingQuery($locale, $user)
            ->whereKeyNot($product->getKey())
            ->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds))
            ->limit(8)->get();
    }
}
