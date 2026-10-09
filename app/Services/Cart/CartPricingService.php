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
        $this->loadCart($cart);
        $this->removeInvalidItems($cart);
        $this->loadCart($cart, true);

        if ($cart->items->isEmpty()) {
            return [
                'items' => [],
                'base_role' => null,
                'order_role' => null,
                'qualification_total' => 0,
                'subtotal' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'currency' => $cart->currency ?: 'RUB',
            ];
        }

        $pricingItems = $cart->items->map(fn ($item): array => [
            'variant' => $item->variant,
            'quantity' => (float) $item->quantity,
        ]);

        $resolved = $this->prices->resolveOrderRole($cart->user, $pricingItems);

        /** @var CustomerRole $baseRole */
        $baseRole = $resolved['base_role'];
        /** @var CustomerRole $orderRole */
        $orderRole = $resolved['order_role'];

        $baseRole->loadMissing('priceType');
        $orderRole->loadMissing('priceType');

        $lines = $cart->items->map(function ($item) use ($orderRole): array {
            $quantity = (float) $item->quantity;
            $available = (float) $item->variant->stock;

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'cart' => "Недостаточный остаток SKU {$item->variant->sku}. Доступно: {$available}.",
                ]);
            }

            $price = $this->prices->priceForVariant($item->variant, $orderRole, $quantity);

            if (! $price) {
                throw ValidationException::withMessages([
                    'cart' => "Для SKU {$item->variant->sku} не настроена цена «{$orderRole->priceType?->name}».",
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
                'old_price' => $this->prices->retailOldAmountForVariant($item->variant, $orderRole, $quantity, $amount),
                'line_total' => $lineTotal,
                'price_id' => $price->id,
                'price_type_id' => $price->product_price_type_id,
                'price_type_code' => $price->priceType?->code,
                'price_type_name' => $price->priceType?->name,
                'image' => $item->variant?->images?->first()?->name,
                'options' => $item->variant?->optionValues?->map(fn ($value): array => [
                        'name' => $value->option?->lang?->name ?? $value->option?->code,
                        'value' => $value->lang?->value ?? $value->code,
                    ])->values()->all() ?? [],
            ];
        })->values();

        $subtotal = round((float) $resolved['qualification_total'], 2);
        $total = round((float) $lines->sum('line_total'), 2);

        return [
            'items' => $lines->all(),
            'base_role' => $this->rolePayload($baseRole),
            'order_role' => $this->rolePayload($orderRole),
            'qualification_total' => $subtotal,
            'subtotal' => $subtotal,
            'discount_amount' => max(0, round($subtotal - $total, 2)),
            'total' => $total,
            'currency' => $cart->currency ?: 'RUB',
        ];
    }

    private function loadCart(Cart $cart, bool $refresh = false): void
    {
        if ($refresh) {
            $cart->unsetRelation('items');
        }

        $cart->load([
            'user.customerRole.priceType',
            'items.product.lang',
            'items.product.prices.priceType',
            'items.variant.lang',
            'items.variant.images',
            'items.variant.prices.priceType',
            'items.variant.optionValues.option.lang',
            'items.variant.optionValues.lang',
        ]);
    }

    private function removeInvalidItems(Cart $cart): void
    {
        $invalidIds = $cart->items
            ->filter(function ($item): bool {
                if (! $item->product || ! $item->variant) {
                    return true;
                }

                if (! $item->product->is_active || ! $item->variant->is_active) {
                    return true;
                }

                return (int) $item->variant->product_id !== (int) $item->product_id;
            })
            ->pluck('id');

        if ($invalidIds->isNotEmpty()) {
            $cart->items()->whereKey($invalidIds)->delete();
        }
    }

    private function rolePayload(CustomerRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'level' => (int) $role->level,
            'price_sort_order' => (int) ($role->priceType?->sort_order ?? 0),
            'price_type_id' => $role->product_price_type_id,
            'price_type_code' => $role->priceType?->code,
            'price_type_name' => $role->priceType?->name,
        ];
    }
}
