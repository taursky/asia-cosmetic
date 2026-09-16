<?php

namespace App\Services\Pricing;

use App\Models\CustomerRole;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerPriceResolver
{
    public function currentRole(User $user): CustomerRole
    {
        return $user->customerRole()->first()
            ?? CustomerRole::query()->where('is_default', true)->orderBy('level')->firstOrFail();
    }

    public function priceForVariant(ProductVariant $variant, CustomerRole $role, float $quantity = 1): ?ProductPrice
    {
        if (! $role->product_price_type_id) {
            return null;
        }

        $base = ProductPrice::query()
            ->where('product_id', $variant->product_id)
            ->where('product_price_type_id', $role->product_price_type_id)
            ->where('min_quantity', '<=', $quantity)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()));

        return (clone $base)
            ->where('product_variant_id', $variant->id)
            ->orderByDesc('min_quantity')
            ->first()
            ?? (clone $base)
                ->whereNull('product_variant_id')
                ->orderByDesc('min_quantity')
                ->first();
    }

    /** @param Collection<int, array{variant: ProductVariant, quantity: float|int}> $items */
    public function resolveOrderRole(User $user, Collection $items): array
    {
        $baseRole = $this->currentRole($user);
        $qualificationTotal = $this->calculateTotal($items, $baseRole);

        $orderRole = CustomerRole::query()
            ->where('is_auto', true)
            ->where('level', '>=', $baseRole->level)
            ->whereNotNull('order_threshold_amount')
            ->where('order_threshold_amount', '<=', $qualificationTotal)
            ->orderByDesc('level')
            ->first() ?? $baseRole;

        return [
            'base_role' => $baseRole,
            'order_role' => $orderRole,
            'qualification_total' => $qualificationTotal,
            'final_total' => $this->calculateTotal($items, $orderRole),
        ];
    }

    /** @param Collection<int, array{variant: ProductVariant, quantity: float|int}> $items */
    public function calculateTotal(Collection $items, CustomerRole $role): float
    {
        return round($items->sum(function (array $line) use ($role): float {
            $quantity = (float) $line['quantity'];
            $price = $this->priceForVariant($line['variant'], $role, $quantity);
            return (float) ($price?->amount ?? 0) * $quantity;
        }), 2);
    }
}
