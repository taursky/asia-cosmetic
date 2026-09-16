<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Cart\CartPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, CartPricingService $pricing): JsonResponse
    {
        $user = $request->user()->load(['customerRole', 'customerProfile']);
        $cart = Cart::query()->with(['items.variant.product'])->where('user_id', $user->id)->firstOrFail();

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Корзина пуста.']);
        }

        $role = $user->customerRole;
        if ($role && (int) $role->level > 0) {
            if (! $user->customerProfile || $user->customerProfile->verification_status !== 'verified') {
                throw ValidationException::withMessages([
                    'profile' => 'Для оформления оптового заказа заполните и подтвердите реквизиты покупателя.',
                ]);
            }
        }

        $quote = $pricing->quote($user, $cart);

        if ($quote['lines']->contains(fn (array $line) => $line['price'] <= 0)) {
            throw ValidationException::withMessages([
                'price' => 'Для одного или нескольких товаров не настроена цена для вашего уровня.',
            ]);
        }

        $order = DB::transaction(function () use ($user, $cart, $quote): Order {
            $orderRole = $quote['order_role'];

            $order = Order::query()->create([
                'user_id' => $user->id,
                'customer_role_id' => $orderRole->id,
                'product_price_type_id' => $orderRole->product_price_type_id,
                'status' => 'new',
                'payment_status' => 'unpaid',
                'subtotal' => $quote['total'],
                'delivery_amount' => 0,
                'discount_amount' => max(0, $quote['qualification_total'] - $quote['total']),
                'total' => $quote['total'],
                'customer_data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'pricing_meta' => [
                    'base_role_id' => $quote['base_role']->id,
                    'order_role_id' => $orderRole->id,
                    'qualification_total' => $quote['qualification_total'],
                    'final_total' => $quote['total'],
                ],
            ]);

            foreach ($quote['lines'] as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'sku' => $line['sku'],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'total' => $line['total'],
                    'meta' => [
                        'product_price_id' => $line['product_price_id'],
                        'customer_role_id' => $orderRole->id,
                        'product_price_type_id' => $orderRole->product_price_type_id,
                    ],
                ]);
            }

            $cart->items()->delete();

            return $order->load('items');
        });

        return response()->json(['order' => $order], 201);
    }
}
