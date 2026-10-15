<?php

namespace App\Services\Orders;

use App\Models\CheckoutSession;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create an invoice order from a validated, immutable checkout snapshot.
     * Expected keys: user_id, currency, subtotal, delivery_amount,
     * discount_amount, total, customer_data, delivery_data, items.
     */
    public function createInvoiceOrder(array $snapshot): Order
    {
        if (empty($snapshot['items'])) {
            throw ValidationException::withMessages(['items' => 'В заказе нет товаров.']);
        }

        return DB::transaction(function () use ($snapshot): Order {
            return $this->persistOrder($snapshot, 'legal', 'invoice', 'awaiting_invoice', 'pending');
        });
    }

    /** Call only after a trusted payment handler has verified the provider response. */
    public function createPaidOrder(CheckoutSession $session, PaymentTransaction $transaction): Order
    {
        return DB::transaction(function () use ($session, $transaction): Order {
            $session = CheckoutSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();
            $transaction = PaymentTransaction::query()->whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($session->customer_type !== 'individual' || $session->payment_method !== 'card'
                || $transaction->checkout_session_id !== $session->getKey()
                || $transaction->type !== 'payment' || $transaction->status !== 'succeeded'
                || $transaction->confirmed_at === null
                || (string) $transaction->amount !== (string) $session->total
                || $transaction->currency !== $session->currency) {
                throw ValidationException::withMessages(['payment' => 'Платёж не подтверждён для этого оформления.']);
            }

            $existing = Order::query()->where('checkout_session_uuid', $session->uuid)->first();
            if ($existing) {
                if ($transaction->order_id === null) {
                    $transaction->update(['order_id' => $existing->id]);
                }
                return $existing->load('items');
            }

            $snapshot = [
                'user_id' => $session->user_id,
                'currency' => $session->currency,
                'subtotal' => $session->subtotal,
                'delivery_amount' => $session->delivery_amount,
                'discount_amount' => $session->discount_amount,
                'total' => $session->total,
                'customer_data' => $session->customer_data,
                'comment' => data_get(
                    $session->customer_data,
                    'checkout_comment'
                ),
                'delivery_data' => $session->delivery_data,
                'pricing_meta' => $session->pricing_snapshot,
                'checkout_session_uuid' => $session->uuid,
                'items' => $session->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_one_c_id' => $item->product_one_c_id,
                    'variant_one_c_id' => $item->variant_one_c_id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'meta' => $item->meta,
                ])->all(),
            ];

            if (empty($snapshot['items'])) {
                throw ValidationException::withMessages(['items' => 'В оформлении нет товаров.']);
            }

            $order = $this->persistOrder($snapshot, 'individual', 'card', 'new', 'paid');
            $order->update(['paid_at' => $transaction->confirmed_at]);
            $transaction->update(['order_id' => $order->id]);
            $session->update(['status' => 'completed', 'completed_at' => now()]);
            $this->removePaidCartItems($session);

            return $order->fresh('items');
        });
    }

    private function removePaidCartItems(CheckoutSession $session): void
    {
        foreach ($session->items as $item) {
            $cartItemId = data_get($item->meta, 'cart_item_id');
            $originalQuantity = data_get($item->meta, 'cart_item_quantity');

            // Старые платёжные сессии без связи с корзиной пропускаем.
            if (!$cartItemId || $originalQuantity === null) {
                continue;
            }

            $cartItem = CartItem::query()
                ->whereKey($cartItemId)
                ->whereHas('cart', function ($query) use ($session) {
                    $query->where('user_id', $session->user_id);
                })
                ->lockForUpdate()
                ->first();

            if (!$cartItem) {
                continue;
            }

            // Если покупатель изменил количество, ничего не удаляем.
            if (round((float) $cartItem->quantity, 3) !== round((float) $originalQuantity, 3)) {
                continue;
            }

            // Дополнительно проверяем, что товар и вариант совпадают.
            if (
                (int) $cartItem->product_id !== (int) $item->product_id ||
                (int) $cartItem->product_variant_id !== (int) $item->product_variant_id
            ) {
                continue;
            }

            $cartItem->delete();
        }
    }

    private function persistOrder(array $snapshot, string $customerType, string $paymentMethod, string $status, string $paymentStatus): Order
    {
        $uuid = (string) Str::uuid();
        $order = Order::query()->create([
            'uuid' => $uuid,
            'number' => 'WEB-' . strtoupper($uuid),
            'user_id' => $snapshot['user_id'] ?? null,
            'customer_role_id' => $snapshot['customer_role_id'] ?? null,
            'product_price_type_id' => $snapshot['product_price_type_id'] ?? null,
            'customer_type' => $customerType,
            'payment_method' => $paymentMethod,
            'checkout_session_uuid' => $snapshot['checkout_session_uuid'] ?? null,
            'revision' => 1,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'currency' => $snapshot['currency'] ?? 'RUB',
            'subtotal' => $snapshot['subtotal'],
            'delivery_amount' => $snapshot['delivery_amount'] ?? '0.00',
            'discount_amount' => $snapshot['discount_amount'] ?? '0.00',
            'total' => $snapshot['total'],
            'customer_data' => $snapshot['customer_data'] ?? null,
            'delivery_data' => $snapshot['delivery_data'] ?? null,
            'payment_data' => ['method' => $paymentMethod],
            'pricing_meta' => $snapshot['pricing_meta'] ?? null,
            'comment' => $snapshot['comment'] ?? null,
            'sync_status' => 'pending',
        ]);

        foreach ($snapshot['items'] as $line) {
            $order->items()->create([
                'product_id' => $line['product_id'] ?? null,
                'product_variant_id' => $line['product_variant_id'] ?? null,
                'product_one_c_id' => $line['product_one_c_id'] ?? null,
                'variant_one_c_id' => $line['variant_one_c_id'] ?? null,
                'sku' => $line['sku'],
                'name' => $line['name'],
                'quantity' => $line['quantity'],
                'price' => $line['price'],
                'total' => $line['total'],
                'meta' => $line['meta'] ?? null,
            ]);
        }

        return $order->load('items');
    }
}
