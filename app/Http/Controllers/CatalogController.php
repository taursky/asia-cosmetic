<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CatalogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $categoryIds = $this->catalog->categoryIdsWithActiveProducts();
        $categories = Category::query()
            ->where('is_active', true)->whereNull('parent_id')->whereIn('id', $categoryIds)
            ->with([
                'lang',
                'children' => fn ($q) => $q->where('is_active', true)->whereIn('id', $categoryIds)->orderBy('sort_order'),
                'children.lang',
            ])->orderBy('sort_order')->get();

        $products = $this->catalog->paginate(
            $this->catalog->sort($this->catalog->listingQuery($locale, $request->user('web')), 'new'),
            $request->user('web')
        );

        return view('catalog.index', compact('categories', 'products'));
    }

    public function category(Request $request, string $categorySlug): View
    {
        $locale = app()->getLocale();
        $visibleIds = $this->catalog->categoryIdsWithActiveProducts();
        $category = Category::query()
            ->where('is_active', true)->whereIn('categories.id', $visibleIds)
            ->whereHas('langs', fn (Builder $q) => $q->where('lang', $locale)->where('slug', $categorySlug))
            ->with([
                'lang', 'parent.lang',
                'children' => fn ($q) => $q->where('is_active', true)->whereIn('id', $visibleIds)->orderBy('sort_order'),
                'children.lang',
            ])->firstOrFail();

        $categoryIds = $this->catalog->categoryTreeIds($category);
        $query = $this->catalog->listingQuery($locale, $request->user('web'))
            ->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds));
        $products = $this->catalog->paginate($this->catalog->sort($query, 'new'), $request->user('web'));

        return view('catalog.category', compact('category', 'products'));
    }

    public function collection(Request $request, string $slug): View
    {
        $preset = config("catalog_collections.{$slug}");
        abort_unless(is_array($preset) && isset($preset['title']), 404);

        $query = $this->catalog->listingQuery(app()->getLocale(), $request->user('web'));
        $query = $this->catalog->applyPreset($query, $preset);
        $products = $this->catalog->paginate($query, $request->user('web'));

        return view('catalog.collection', compact('preset', 'products', 'slug'));
    }

    public function product(Request $request, string $productSlug): View
    {
        $locale = app()->getLocale();
        $user = $request->user('web');
        $product = $this->catalog->detailQuery($locale, $user)
            ->whereHas('langs', fn (Builder $q) => $q->where('lang', $locale)->where('slug', $productSlug))
            ->firstOrFail();

        $this->catalog->decorateProduct($product, $user);
        $relatedProducts = $this->catalog->relatedProducts($product, $locale, $user);
        $this->catalog->decorateCollection($relatedProducts, $user);

        return view('catalog.product', compact('product', 'relatedProducts'));
    }
}
