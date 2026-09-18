<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartPricingService;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CartPricingService $pricing,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->cartFor($request->user());

        return response()->json(
            $this->pricing->calculate($cart)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $variant = ProductVariant::query()
            ->with('product')
            ->findOrFail($data['variant_id']);

        $cart = $this->carts->add(
            $request->user(),
            $variant,
            (float) $data['quantity'],
        );

        return response()->json(
            $this->pricing->calculate($cart)
        );
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $item->load(['cart', 'variant']);

        $cart = $this->carts->update(
            $request->user(),
            $item,
            (float) $data['quantity'],
        );

        return response()->json(
            $this->pricing->calculate($cart)
        );
    }

    public function destroy(Request $request, CartItem $item): JsonResponse
    {
        $item->load('cart');

        $cart = $this->carts->remove(
            $request->user(),
            $item,
        );

        return response()->json(
            $this->pricing->calculate($cart)
        );
    }
}
