<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->getKey();

        $products = Product::query()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->whereHas('favoritedBy', fn ($q) => $q->whereKey($userId))
            ->withExists(['favoritedBy as is_favorite' => fn ($q) => $q->whereKey($userId)])
            ->with([
                'lang',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position'),
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')
                    ->with([
                        'images' => fn ($images) => $images->orderByDesc('is_primary')->orderBy('position'),
                        'prices.priceType',
                    ]),
                'prices.priceType',
            ])
            ->orderByDesc(
                DB::table('favorite_products')
                    ->select('created_at')
                    ->whereColumn('favorite_products.product_id', 'products.id')
                    ->where('user_id', $userId)
                    ->limit(1)
            )
            ->paginate(12);

        return view('favorites.index', compact('products'));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active && $product->is_visible, 404);

        $request->user()->favoriteProducts()->syncWithoutDetaching([$product->getKey()]);

        return response()->json(['is_favorite' => true]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->favoriteProducts()->detach($product->getKey());

        return response()->json(['is_favorite' => false]);
    }
}
