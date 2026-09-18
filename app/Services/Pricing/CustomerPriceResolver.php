<?php

namespace App\Services\Pricing;

use App\Models\CustomerRole;
use App\Models\ProductPrice;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CustomerPriceResolver
{
    public function currentRole(User $user): CustomerRole
    {
        $role = $user->customerRole;

        if ($role && $role->is_active) {
            return $role;
        }

        $role = CustomerRole::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('level')
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'pricing' => 'Не настроена базовая роль покупателя. Создайте активную роль с флагом «По умолчанию».',
            ]);
        }

        // Пользователь без роли получает базовую роль сразу,
        // чтобы последующие запросы корзины были однозначными.
        if (! $user->customer_role_id) {
            $user->forceFill([
                'customer_role_id' => $role->id,
                'customer_role_valid_until' => $role->validity_days
                    ? now()->addDays((int) $role->validity_days)
                    : null,
            ])->save();

            $user->setRelation('customerRole', $role);
        }

        return $role;
    }

    public function priceForVariant(
        ProductVariant $variant,
        CustomerRole $role,
        float $quantity = 1,
    ): ?ProductPrice {
        if (! $role->product_price_type_id) {
            return null;
        }

        $base = ProductPrice::query()
            ->where('product_id', $variant->product_id)
            ->where('product_price_type_id', $role->product_price_type_id)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->orderByDesc('min_quantity');

        return (clone $base)
            ->where('product_variant_id', $variant->id)
            ->first()
            ?? (clone $base)
                ->whereNull('product_variant_id')
                ->first();
    }

    /**
     * @param Collection<int, array{variant: ProductVariant, quantity: float|int}> $items
     */
    public function resolveOrderRole(User $user, Collection $items): array
    {
        $baseRole = $this->currentRole($user);
        $baseTotal = $this->calculateTotal($items, $baseRole);

        $orderRole = CustomerRole::query()
            ->where('is_active', true)
            ->where('is_auto', true)
            ->where('level', '>=', $baseRole->level)
            ->whereNotNull('order_threshold_amount')
            ->where('order_threshold_amount', '<=', $baseTotal)
            ->orderByDesc('level')
            ->first() ?? $baseRole;

        return [
            'base_role' => $baseRole,
            'order_role' => $orderRole,
            'qualification_total' => $baseTotal,
            'final_total' => $this->calculateTotal($items, $orderRole),
        ];
    }

    /**
     * @param Collection<int, array{variant: ProductVariant, quantity: float|int}> $items
     */
    public function calculateTotal(Collection $items, CustomerRole $role): float
    {
        return round($items->sum(function (array $line) use ($role): float {
            $quantity = (float) $line['quantity'];
            $price = $this->priceForVariant($line['variant'], $role, $quantity);

            return (float) ($price?->amount ?? 0) * $quantity;
        }), 2);
    }
}
