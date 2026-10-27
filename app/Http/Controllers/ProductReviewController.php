<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductReviewController extends Controller
{
    public function index(): View
    {
        $locale = app()->getLocale();
        $products = Product::query()
            ->where('is_active', true)->where('is_visible', true)
            ->whereHas('langs', fn ($q) => $q->where('lang', $locale))
            ->whereHas('reviews', fn ($q) => $q->published())
            ->with(['lang', 'reviews' => fn ($q) => $q->published()
                ->with('user:id,name')->latest('published_at')->limit(3)])
            ->withCount([
                'reviews as published_reviews_count' => fn ($q) => $q->published(),
                'reviews as positive_reviews_count' => fn ($q) => $q->published()->where('rating', '>', 3),
            ])
            ->orderByDesc('positive_reviews_count')
            ->orderByDesc('published_reviews_count')
            ->orderBy('products.id')
            ->paginate(12);

        return view('reviews.index', compact('products'));
    }

    public function product(string $productSlug): View
    {
        $locale = app()->getLocale();
        $product = Product::query()->where('is_active', true)->where('is_visible', true)
            ->whereHas('langs', fn ($q) => $q->where('lang', $locale)->where('slug', $productSlug))
            ->with('lang')->firstOrFail();

        $reviews = $product->reviews()->published()->with('user:id,name')
            ->latest('published_at')->paginate(15);

        return view('reviews.product', compact('product', 'reviews'));
    }

    public function store(StoreProductReviewRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->is_visible, 404);
        $data = $request->validated();
        $review = ProductReview::query()->firstOrNew([
            'product_id' => $product->getKey(),
            'user_id' => $request->user('web')->getAuthIdentifier(),
        ]);
        $review->fill($data);
        $review->status = 'pending';
        $review->published_at = null;
        $review->save();

        return back()->with('review_success', 'Отзыв отправлен на модерацию.');
    }
}
