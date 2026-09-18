<?php

namespace App\Services\Cart;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly CartService $carts,
    ) {}

    public function checkout(\App\Models\Cart $cart, array $data): Order
    {
        $user = $cart->user()->with(['customerRole', 'customerProfile'])->firstOrFail();
        $result = $this->pricing->calculate($cart);

        if (empty($result['items'])) {
            throw ValidationException::withMessages(['cart' => 'Корзина пуста.']);
        }

        if (($result['order_role']['level'] ?? 0) > 0) {
            $profile = $user->customerProfile;

            if (! $profile || $profile->verification_status !== 'verified') {
                throw ValidationException::withMessages([
                    'profile' => 'Для оформления оптового заказа заполните и подтвердите платёжные реквизиты.',
                ]);
            }
        }

        return DB::transaction(function () use ($cart, $user, $result, $data): Order {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'uuid' => (string) Str::uuid(),
                'number' => $this->nextNumber(),
                'status' => 'new',
                'payment_status' => 'pending',
                'currency' => $result['currency'],
                'subtotal' => $result['subtotal'],
                'delivery_amount' => 0,
                'discount_amount' => $result['discount_amount'],
                'total' => $result['total'],
                'customer_role_id' => $result['order_role']['id'],
                'product_price_type_id' => $user->customerRole?->product_price_type_id,
                'customer_data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'profile' => $user->customerProfile?->toArray(),
                ],
                'delivery_data' => [
                    'method' => $data['delivery_method'],
                    'address' => $data['delivery_address'] ?? null,
                ],
                'payment_data' => [
                    'method' => $data['payment_method'],
                ],
                'comment' => $data['comment'] ?? null,
                'pricing_meta' => [
                    'base_role' => $result['base_role'],
                    'order_role' => $result['order_role'],
                    'qualification_total' => $result['qualification_total'],
                    'discount_amount' => $result['discount_amount'],
                ],
                'sync_status' => 'pending',
            ]);

            foreach ($result['items'] as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['variant_id'],
                    'sku' => $line['sku'],
                    'name' => $line['product_name'] ?: $line['variant_name'] ?: $line['sku'],
                    'quantity' => $line['quantity'],
                    'price' => $line['unit_price'],
                    'total' => $line['line_total'],
                    'meta' => [
                        'product_price_id' => $line['price_id'],
                        'customer_role_id' => $result['order_role']['id'],
                        'options' => $line['options'],
                    ],
                ]);
            }

            $this->carts->clear($cart);

            return $order->fresh('items');
        });
    }

    private function nextNumber(): string
    {
        $next = (int) Order::query()->max('id') + 1;

        return 'WEB-' . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
    }
}
