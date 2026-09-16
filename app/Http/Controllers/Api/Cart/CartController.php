<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Services\Cart\CartPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cart(Request $request): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $request->user()->id]);
    }

    public function show(Request $request, CartPricingService $pricing): JsonResponse
    {
        $cart = $this->cart($request)->load('items.variant.lang');
        return response()->json(['cart' => $cart, 'quote' => $pricing->quote($request->user(), $cart)]);
    }

    public function add(Request $request, CartPricingService $pricing): JsonResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $variant = ProductVariant::query()->where('is_active', true)->findOrFail($data['product_variant_id']);
        $cart = $this->cart($request);

        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $item->quantity = (float) ($item->exists ? $item->quantity : 0) + (float) $data['quantity'];
        $item->save();

        return response()->json(['cart' => $cart->fresh('items.variant.lang'), 'quote' => $pricing->quote($request->user(), $cart->fresh())]);
    }

    public function update(Request $request, int $item, CartPricingService $pricing): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'numeric', 'min:0.001']]);
        $cart = $this->cart($request);
        $cartItem = $cart->items()->findOrFail($item);
        $cartItem->update(['quantity' => $data['quantity']]);

        return response()->json(['quote' => $pricing->quote($request->user(), $cart->fresh())]);
    }

    public function destroy(Request $request, int $item, CartPricingService $pricing): JsonResponse
    {
        $cart = $this->cart($request);
        $cart->items()->findOrFail($item)->delete();
        return response()->json(['quote' => $pricing->quote($request->user(), $cart->fresh())]);
    }
}
