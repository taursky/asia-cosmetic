<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StaticPage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $locale = app()->getLocale();

        $baseProductQuery = fn (): Builder => Product::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->whereHas('prices', function ($query)  {
                //todo: изменить, когда будут цены
                $query->where('product_price_type_id', 6);
            })
            ->whereHas('lang', fn (Builder $query) => $query->where('lang', $locale))
            ->with([
                'lang',
                'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('position'),
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with([
                        'images' => fn ($imageQuery) => $imageQuery->orderByDesc('is_primary')->orderBy('position'),
                        'prices.priceType',
                    ]),
                'prices.priceType',
            ]);

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
