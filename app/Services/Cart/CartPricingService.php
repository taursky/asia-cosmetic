<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\User;
use App\Services\Pricing\CustomerPriceResolver;

class CartPricingService
{
    public function __construct(private readonly CustomerPriceResolver $prices) {}

    public function quote(User $user, Cart $cart): array
    {
        $cart->loadMissing(['items.variant.product']);

        $items = $cart->items->map(fn ($item): array => [
            'variant' => $item->variant,
            'quantity' => (float) $item->quantity,
        ]);

        $roleInfo = $this->prices->resolveOrderRole($user, $items);
        $orderRole = $roleInfo['order_role'];

        $lines = $cart->items->map(function ($item) use ($orderRole): array {
            $price = $this->prices->priceForVariant($item->variant, $orderRole, (float) $item->quantity);
            $amount = (float) ($price?->amount ?? 0);
            $quantity = (float) $item->quantity;

            return [
                'cart_item_id' => $item->id,
                'product_id' => $item->variant->product_id,
                'product_variant_id' => $item->variant->id,
                'sku' => $item->variant->sku,
                'quantity' => $quantity,
                'price' => $amount,
                'total' => round($amount * $quantity, 2),
                'product_price_id' => $price?->id,
            ];
        });

        return [
            ...$roleInfo,
            'lines' => $lines,
            'total' => round($lines->sum('total'), 2),
        ];
    }
}
