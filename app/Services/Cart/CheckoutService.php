<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(private readonly CartPricingService $pricing, private readonly CartService $carts, private readonly OrderService $orders) {}

    public function checkout(Cart $cart, array $data, ?array $selectedItemIds = null): Order|CheckoutSession
    {
        $user = $cart->user()->with(['customerRole.priceType', 'customerProfile'])->firstOrFail();
        $profile = $user->customerProfile;

        $isBusinessProfile = $profile !== null && in_array($profile->legal_type, [
                CustomerProfile::LEGAL_TYPE_ENTITY,
                CustomerProfile::LEGAL_TYPE_IP,
            ], true);

        if ($isBusinessProfile && (
                blank($profile->inn) ||
                (blank($profile->company_name) && blank($profile->full_company_name))
            )) {
            throw ValidationException::withMessages([
                'profile' => 'Для оформления заказа юридического лица или ИП заполните ИНН и наименование.',
            ]);
        }

        $isLegal = $isBusinessProfile;
        $expectedPaymentMethod = $isLegal ? 'invoice' : 'card';

        if (isset($data['payment_method']) && $data['payment_method'] !== $expectedPaymentMethod) {
            throw ValidationException::withMessages([
                'payment_method' => 'Выбранный способ оплаты недоступен для этого типа покупателя.',
            ]);
        }

        if (($data['delivery_method'] ?? 'pickup') === 'delivery' && blank($data['delivery_address'] ?? null)) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Укажите адрес доставки.',
            ]);
        }
        if ($selectedItemIds !== null) {
            $selectedItemIds = array_values(array_unique(
                array_map('intval', $selectedItemIds)
            ));

            if (empty($selectedItemIds)) {
                throw ValidationException::withMessages([
                    'cart' => 'Не выбраны товары для оформления.',
                ]);
            }

            $existingCount = $cart->items()
                ->whereIn('id', $selectedItemIds)
                ->count();

            if ($existingCount !== count($selectedItemIds)) {
                throw ValidationException::withMessages([
                    'cart' => 'Некоторые выбранные товары отсутствуют в корзине.',
                ]);
            }
        }
        $result = $this->pricing->calculate($cart, $selectedItemIds);

        if (empty($result['items'])) {
            throw ValidationException::withMessages(['cart' => 'Корзина пуста.']);
        }

        if (($result['order_role']['price_sort_order'] ?? 0) > ($result['base_role']['price_sort_order'] ?? 0) || ($result['order_role']['price_sort_order'] ?? 0) > 0) {
            if (! $profile ) {//|| $profile->verification_status !== CustomerProfile::STATUS_VERIFIED) {
                throw ValidationException::withMessages([
                    'profile' => 'Для оформления оптового заказа заполните и подтвердите платёжные реквизиты.',
                ]);
            }
        }

        $snapshot = [
            'user_id' => $user->id,
            'currency' => $result['currency'],
            'subtotal' => $this->money($result['subtotal']),
            'delivery_amount' => '0.00',
            'discount_amount' => $this->money($result['discount_amount']),
            'total' => $this->money($result['total']),
            'customer_role_id' => $result['order_role']['id'],
            'product_price_type_id' => $result['order_role']['price_type_id'],
            'customer_data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile' => $profile?->toArray(),
            ],
            'delivery_data' => [
                'method' => $data['delivery_method'] ?? 'pickup',
                'address' => $data['delivery_address'] ?? null,
            ],
            'comment' => $data['comment'] ?? null,
            'pricing_meta' => [
                'base_role' => $result['base_role'],
                'order_role' => $result['order_role'],
                'qualification_total' => $result['qualification_total'],
                'discount_amount' => $result['discount_amount'],
            ],
            'items' => array_map(fn (array $line): array => [
                'product_id' => $line['product_id'],
                'product_variant_id' => $line['variant_id'],
                'product_one_c_id' => $line['product_one_c_id'] ?? null,
                'variant_one_c_id' => $line['variant_one_c_id'] ?? null,
                'sku' => $line['sku'],
                'name' => $line['product_name'] ?: $line['variant_name'] ?: $line['sku'],
                'quantity' => $line['quantity'],
                'price' => $this->money($line['unit_price']),
                'total' => $this->money($line['line_total']),
                'meta' => [
                    'cart_item_id' => $line['id'],
                    'cart_item_quantity' => $line['quantity'],
                    'image' => $line['image'] ?? null,
                    'product_price_id' => $line['price_id'],
                    'customer_role_id' => $result['order_role']['id'],
                    'product_price_type_id' => $line['price_type_id'],
                    'price_type_code' => $line['price_type_code'],
                    'price_type_name' => $line['price_type_name'],
                    'options' => $line['options'],
                ],
            ], $result['items']),
        ];

        return DB::transaction(function () use ($cart, $snapshot, $isLegal): Order|CheckoutSession {
            if ($isLegal) {
                $order = $this->orders->createInvoiceOrder($snapshot);

                $cartItemIds = array_column(
                    array_column($snapshot['items'], 'meta'),
                    'cart_item_id'
                );

                $cart->items()
                    ->whereIn('id', $cartItemIds)
                    ->delete();

                return $order;
            }

            $session = CheckoutSession::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $snapshot['user_id'],
                'status' => 'pending_payment',
                'expires_at' => now()->addMinutes(30),
                'customer_type' => 'individual',
                'payment_method' => 'card',
                'currency' => $snapshot['currency'],
                'subtotal' => $snapshot['subtotal'],
                'delivery_amount' => $snapshot['delivery_amount'],
                'discount_amount' => $snapshot['discount_amount'],
                'total' => $snapshot['total'],
                'customer_data' => array_merge(
                    $snapshot['customer_data'] ?? [],
                    ['checkout_comment' => $snapshot['comment'] ?? null]
                ),
                'delivery_data' => $snapshot['delivery_data'],
                'pricing_snapshot' => $snapshot['pricing_meta'],
            ]);

            foreach ($snapshot['items'] as $line) {
                $session->items()->create($line);
            }

            return $session->load('items');
        });
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
