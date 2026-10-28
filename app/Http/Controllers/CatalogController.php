<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CatalogCollection;
use App\Models\Product;
use App\Services\Catalog\CatalogService;
use App\Services\Catalog\BreadcrumbService;
use App\Services\Catalog\ProductSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $catalog, private readonly BreadcrumbService $breadcrumbs, private readonly ProductSearch $productSearch)
    {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $categoryIds = $this->catalog->categoryIdsWithActiveProducts();
        $categories = Category::query()
            ->where('is_active', true)->whereNull('parent_id')->whereIn('id', $categoryIds)
            ->with([
                'lang',
                'children' => fn($q) => $q->where('is_active', true)->whereIn('id', $categoryIds)->orderBy('sort_order'),
                'children.lang',
            ])->orderBy('sort_order')->get();

        $products = $this->catalog->paginate(
            $this->catalog->sort($this->catalog->listingQuery($locale, $request->user('web')), 'new'),
            $request->user('web')
        );

        $breadcrumbs = $this->breadcrumbs->catalog();
        return view('catalog.index', compact('categories', 'products', 'breadcrumbs'));
    }

    public function category(Request $request, string $categorySlug): View
    {
        $locale = app()->getLocale();
        $visibleIds = $this->catalog->categoryIdsWithActiveProducts();
        $category = Category::query()
            ->where('is_active', true)->whereIn('categories.id', $visibleIds)
            ->whereHas('langs', fn(Builder $q) => $q->where('lang', $locale)->where('slug', $categorySlug))
            ->with([
                'lang', 'parent.lang',
                'children' => fn($q) => $q->where('is_active', true)->whereIn('id', $visibleIds)->orderBy('sort_order'),
                'children.lang',
            ])->firstOrFail();

        $categoryIds = $this->catalog->categoryTreeIds($category);
        $query = $this->catalog->listingQuery($locale, $request->user('web'))
            ->whereHas('categories', fn(Builder $q) => $q->whereIn('categories.id', $categoryIds));
        $products = $this->catalog->paginate($this->catalog->sort($query, 'new'), $request->user('web'));

        $breadcrumbs = $this->breadcrumbs->category($category);
        return view('catalog.category', compact('category', 'products', 'breadcrumbs'));
    }

    public function collection(Request $request, string $slug): View
    {
        $collection = CatalogCollection::query()->published()
            ->where('slug', $slug)->firstOrFail();

        $preset = [
            'title' => $collection->title,
            'description' => $collection->description,
            'seo_title' => $collection->seo_title,
            'seo_description' => $collection->seo_description,
        ];

        $query = $this->catalog->listingQuery(app()->getLocale(), $request->user('web'));
        $products = $this->catalog->paginate(
            $this->catalog->applyCollection($query, $collection),
            $request->user('web')
        );

        $breadcrumbs = $this->breadcrumbs->collection($preset['title']);
        return view('catalog.collection', compact('preset', 'products', 'slug', 'collection', 'breadcrumbs'));
    }

    public function product(Request $request, string $productSlug): View
    {
        $locale = app()->getLocale();
        $user = $request->user('web');
        $product = $this->catalog->detailQuery($locale, $user)
            ->whereHas('langs', fn(Builder $q) => $q->where('lang', $locale)->where('slug', $productSlug))
            ->with([
                'reviews' => fn ($query) => $query->published()
                    ->with(['user:id,name', 'media'])
                    ->latest('published_at')
                    ->limit(5),
            ])
            ->firstOrFail();

        $this->catalog->decorateProduct($product, $user);
        $relatedProducts = $this->catalog->relatedProducts($product, $locale, $user);
        $this->catalog->decorateCollection($relatedProducts, $user);

        $breadcrumbs = $this->breadcrumbs->product($product);
        return view('catalog.product', compact('product', 'relatedProducts', 'breadcrumbs'));
    }

    public function search(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $q = trim(preg_replace('/\s+/u', ' ', $data['q'] ?? ''));
        $products = null;

        if ($q !== '') {
            $user = $request->user('web');
            $query = $this->catalog->listingQuery(app()->getLocale(), $user);
            $this->productSearch->apply($query, $q, app()->getLocale());
            $products = $this->catalog->paginate($query, $user)->withQueryString();
        }

        $breadcrumbs = $this->breadcrumbs->catalog();
        return view('catalog.search', compact('q', 'products', 'breadcrumbs'));
    }
}
