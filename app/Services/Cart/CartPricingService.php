<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CustomerRole;
use App\Services\Pricing\CustomerPriceResolver;
use Illuminate\Validation\ValidationException;

class CartPricingService
{
    public function __construct(
        private readonly CustomerPriceResolver $prices,
    ) {}

    public function calculate(Cart $cart): array
    {
        $cart->load([
            'user.customerRole.priceType',
            'items.product.lang',
            'items.variant.lang',
            'items.variant.images',
            'items.variant.optionValues.option.lang',
            'items.variant.optionValues.lang',
        ]);

        if ($cart->items->isEmpty()) {
            return [
                'items' => [],
                'base_role' => null,
                'order_role' => null,
                'qualification_total' => 0,
                'subtotal' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'currency' => $cart->currency,
            ];
        }

        $pricingItems = $cart->items->map(fn ($item): array => [
            'variant' => $item->variant,
            'quantity' => (float) $item->quantity,
        ]);

        $resolved = $this->prices->resolveOrderRole($cart->user, $pricingItems);

        /** @var CustomerRole $orderRole */
        $orderRole = $resolved['order_role'];

        $lines = $cart->items->map(function ($item) use ($orderRole): array {
            $quantity = (float) $item->quantity;
            $price = $this->prices->priceForVariant($item->variant, $orderRole, $quantity);

            if (! $price) {
                throw ValidationException::withMessages([
                    'cart' => "Для SKU {$item->variant->sku} не настроена цена для уровня «{$orderRole->name}».",
                ]);
            }

            $available = (float) $item->variant->stock;

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'cart' => "Недостаточный остаток SKU {$item->variant->sku}. Доступно: {$available}.",
                ]);
            }

            $amount = (float) $price->amount;
            $lineTotal = round($amount * $quantity, 2);

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'product_name' => $item->product?->lang?->name,
                'variant_name' => $item->variant?->lang?->name,
                'sku' => $item->variant?->sku,
                'quantity' => $quantity,
                'stock' => $available,
                'unit_price' => $amount,
                'line_total' => $lineTotal,
                'price_id' => $price->id,
                'image' => $item->variant?->images?->first()?->name,
                'options' => $item->variant?->optionValues?->map(fn ($value): array => [
                        'name' => $value->option?->lang?->name ?? $value->option?->code,
                        'value' => $value->lang?->value ?? $value->code,
                    ])->values()->all() ?? [],
            ];
        })->values();

        $subtotal = round((float) $resolved['qualification_total'], 2);
        $total = round($lines->sum('line_total'), 2);

        return [
            'items' => $lines->all(),
            'base_role' => [
                'id' => $resolved['base_role']->id,
                'name' => $resolved['base_role']->name,
                'level' => $resolved['base_role']->level,
            ],
            'order_role' => [
                'id' => $orderRole->id,
                'name' => $orderRole->name,
                'level' => $orderRole->level,
            ],
            'qualification_total' => $subtotal,
            'subtotal' => $subtotal,
            'discount_amount' => max(0, round($subtotal - $total, 2)),
            'total' => $total,
            'currency' => $cart->currency,
        ];
    }
}
