<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function cartFor(User $user): Cart
    {
        return Cart::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'RUB'],
        );
    }

    public function add(User $user, ProductVariant $variant, float $quantity): Cart
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Количество должно быть больше нуля.']);
        }

        if (! $variant->is_active || ! $variant->product?->is_active) {
            throw ValidationException::withMessages(['variant' => 'Этот товар сейчас недоступен для заказа.']);
        }

        if ((float) $variant->stock <= 0) {
            throw ValidationException::withMessages(['variant' => 'Товар отсутствует на складе.']);
        }

        $cart = $this->cartFor($user);

        DB::transaction(function () use ($cart, $variant, $quantity): void {
            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = ($item ? (float) $item->quantity : 0) + $quantity;

            if ($newQuantity > (float) $variant->stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Запрошенное количество превышает доступный остаток.',
                ]);
            }

            CartItem::query()->updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'product_variant_id' => $variant->id,
                ],
                [
                    'product_id' => $variant->product_id,
                    'quantity' => $newQuantity,
                ],
            );
        });

        return $cart->fresh();
    }

    public function update(User $user, CartItem $item, float $quantity): Cart
    {
        abort_unless((int) $item->cart?->user_id === (int) $user->id, 404);

        if ($quantity <= 0) {
            $cart = $item->cart;
            $item->delete();

            return $cart->fresh();
        }

        if ($quantity > (float) $item->variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Запрошенное количество превышает доступный остаток.',
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $item->cart->fresh();
    }

    public function remove(User $user, CartItem $item): Cart
    {
        abort_unless((int) $item->cart?->user_id === (int) $user->id, 404);

        $cart = $item->cart;
        $item->delete();

        return $cart->fresh();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
