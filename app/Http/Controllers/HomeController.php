<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StaticPage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();

        $userId = auth('web')->id();
        $validPrice = static fn (Builder $query) => $query
            ->where('amount', '>', 0)
            ->where('min_quantity', '<=', 1)
            ->where(fn (Builder $q) => $q
                ->whereNull('valid_from')
                ->orWhere('valid_from', '<=', now()))
            ->where(fn (Builder $q) => $q
                ->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now()))
            ->whereHas('priceType', fn (Builder $q) =>
            $q->where('is_active', true)
            );
        $baseProductQuery = fn (): Builder => Product::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->when($userId !== null, fn (Builder $query) =>
            $query->withExists([
                'favoritedBy as is_favorite' => fn (Builder $favoriteQuery) =>
                $favoriteQuery->where('users.id', $userId),
            ])
            )
            ->where(fn (Builder $query) => $query
                ->whereHas('prices', $validPrice)
                ->orWhereHas('variants', fn (Builder $variantQuery) =>
                $variantQuery
                    ->where('is_active', true)
                    ->whereHas('prices', $validPrice)
                )
            )
            ->whereHas('lang', fn (Builder $query) => $query->where('lang', $locale))
//            ->with([
//                'lang',
//                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('position'),
//                'variants' => fn ($query) => $query
//                    ->where('is_active', true)
//                    ->orderBy('sort_order')
//                    ->with([
//                        'images' => fn ($imageQuery) => $imageQuery->orderByDesc('is_primary')->orderBy('position'),
//                        'prices.priceType',
//                    ]),
//                'prices.priceType',
//            ])
        ;

        $saleProducts = $baseProductQuery()
            ->whereHas('prices', fn (Builder $query) => $query
                ->whereNotNull('old_amount')
                ->whereColumn('old_amount', '>', 'amount'))
            ->limit(12)
            ->get();

        $hitProducts = $baseProductQuery()
            ->orderByDesc('reviews_count')
            ->orderByDesc('rating')
            ->limit(12)
            ->get();

        $newProducts = $baseProductQuery()
            ->latest('created_at')
            ->limit(12)
            ->get();

        // Уникальные товары из всех трёх подборок.
        $allProducts = new EloquentCollection(
            $saleProducts
                ->concat($hitProducts)
                ->concat($newProducts)
                ->unique('id')
                ->values()
                ->all()
        );

        // Загружаем связи один раз.
        $allProducts->load([
            'lang',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('position'),
            'variants' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with([
                    'images' => fn ($imageQuery) => $imageQuery
                        ->orderByDesc('is_primary')
                        ->orderBy('position'),
                    'prices.priceType',
                ]),
            'prices.priceType',
        ]);

        // Рассчитываем цены один раз для каждого уникального товара.
        app(\App\Services\Pricing\CatalogPricePresenter::class)
            ->decorateCollection($allProducts, auth('web')->user());
        // Подставляем подготовленные модели обратно в подборки.
        $productsById = $allProducts->keyBy('id');
        $saleProducts = $saleProducts->map(fn (Product $product) => $productsById->get($product->id));
        $hitProducts = $hitProducts->map(fn (Product $product) => $productsById->get($product->id));
        $newProducts = $newProducts->map(fn (Product $product) => $productsById->get($product->id));

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'lang',
                'children' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('lang')
                    ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->limit(12)
            ->get();

        $banners = Banner::query()
            ->active()
            ->ordered()
            ->get([
                'id',
                'desktop_image',
                'mobile_image',
                'link',
            ])
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'desktop_image' => $banner->desktop_image
                    ? Storage::disk('public')->url($banner->desktop_image)
                    : null,
                'mobile_image' => $banner->mobile_image
                    ? Storage::disk('public')->url($banner->mobile_image)
                    : null,
                'link' => $banner->link,
            ]);

        return view('home', compact(
            'categories',
            'saleProducts',
            'hitProducts',
            'newProducts',
            'banners',
        ));
    }

    /**
     * @param Request $request
     * @param string $slugOrId
     * @return \Illuminate\Contracts\View\Factory|View
     */
    public function show(Request $request, string $slugOrId)
    {
        $validated = $request->validate([
            'lang' => ['sometimes', 'string', Rule::in(['ru', 'en', 'ja', 'zh'])],
        ]);

        $lang = $validated['lang'] ?? app()->getLocale();

        $page = StaticPage::query()
            ->active()
            ->with('langs')
            ->where(function ($query) use ($slugOrId, $lang) {
                if (ctype_digit($slugOrId)) {
                    $query->whereKey((int) $slugOrId);
                    return;
                }
                $query->where('slug', $slugOrId)
                    ->orWhereHas('langs', fn ($t) => $t
                        ->where('lang', $lang)
                        ->where('slug', $slugOrId));
            })
            ->firstOrFail();

        $translation = $page->translation($lang) ?? abort(404);

        return view('content.static-page', compact('translation'));
    }
}
