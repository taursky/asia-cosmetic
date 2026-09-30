<?php

namespace App\Services\Pricing;

use App\Models\CustomerRole;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceType;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CustomerPriceResolver
{
    public function currentRole(User $user): CustomerRole
    {
        $user->loadMissing('customerRole.priceType');

        $role = $user->customerRole;

        if ($role && $role->is_active && ($user->customer_role_locked || ! $user->customer_role_valid_until || Carbon::parse($user->customer_role_valid_until)->isFuture())) {
            return $role;
        }

        $default = CustomerRole::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->with('priceType')
            ->orderBy('level')
            ->first();

        if (! $default) {
            throw ValidationException::withMessages([
                'pricing' => 'Не настроена базовая роль покупателя.',
            ]);
        }

        return $default;
    }

    public function priceTypeFor(?User $user): ProductPriceType
    {
        if ($user) {
            $role = $this->currentRole($user);

            if ($role->priceType?->is_active) {
                return $role->priceType;
            }
        }

        $defaultRole = CustomerRole::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->with('priceType')
            ->orderBy('level')
            ->first();

        if ($defaultRole?->priceType?->is_active) {
            return $defaultRole->priceType;
        }

        $retail = ProductPriceType::query()
            ->where('is_active', true)
            ->where('code', 'retail')
            ->first();

        if ($retail) {
            return $retail;
        }

        $fallback = ProductPriceType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $fallback) {
            throw ValidationException::withMessages([
                'pricing' => 'Не настроен ни один активный тип цены.',
            ]);
        }

        return $fallback;
    }

    public function displayPriceForProduct(Product $product, ?User $user): array
    {
        $type = $this->priceTypeFor($user);
        $quotes = collect();

        foreach ($product->variants->where('is_active', true) as $variant) {
            $quote = $this->displayPriceForVariant($variant, $user, $type);

            if ($quote['amount'] !== null) {
                $quotes->push($quote);
            }
        }

        if ($quotes->isEmpty()) {
            $price = $this->pickLoadedPrice($product->prices, $type->id, 1);

            if (! $price && $type->code !== 'retail') {
                $retail = $this->retailPriceType();
                $price = $retail ? $this->pickLoadedPrice($product->prices, $retail->id, 1) : null;
                $type = $price?->priceType ?: $type;
            }

            return $this->quotePayload($price, $type, false, null);
        }

        $quote = $quotes->sortBy('amount')->first();
        $quote['is_from'] = $quotes->pluck('amount')->unique()->count() > 1;

        return $quote;
    }

    public function displayPriceForVariant(
        ProductVariant $variant,
        ?User $user,
        ?ProductPriceType $type = null,
    ): array {
        $type ??= $this->priceTypeFor($user);

        $price = $this->pickLoadedPrice($variant->prices, $type->id, 1)
            ?? $this->pickLoadedPrice($variant->product?->prices ?? collect(), $type->id, 1);

        if (! $price && $type->code !== 'retail') {
            $retail = $this->retailPriceType();

            if ($retail) {
                $price = $this->pickLoadedPrice($variant->prices, $retail->id, 1)
                    ?? $this->pickLoadedPrice($variant->product?->prices ?? collect(), $retail->id, 1);

                if ($price) {
                    $type = $retail;
                }
            }
        }

        return $this->quotePayload($price, $type, false, $variant->id);
    }

    public function priceForVariant(ProductVariant $variant, CustomerRole $role, float $quantity = 1,): ?ProductPrice
    {
        $priceTypeId = $role->product_price_type_id;

        if (! $priceTypeId) {
            return null;
        }

        $price = $this->priceQuery($priceTypeId, $quantity)
            ->where('product_id', $variant->product_id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($price) {
            return $price;
        }

        return $this->priceQuery($priceTypeId, $quantity)
            ->where('product_id', $variant->product_id)
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
            ->whereNotNull('product_price_type_id')
            ->where('level', '>=', $baseRole->level)
            ->whereNotNull('order_threshold_amount')
            ->where('order_threshold_amount', '<=', $baseTotal)
            ->with('priceType')
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
            $variant = $line['variant'] ?? null;

            if (! $variant instanceof ProductVariant) {
                throw ValidationException::withMessages([
                    'cart' => 'В корзине обнаружена устаревшая позиция товара. Обновите корзину и добавьте товар заново.',
                ]);
            }

            $quantity = (float) ($line['quantity'] ?? 0);

            if ($quantity <= 0) {
                return 0.0;
            }

            $price = $this->priceForVariant($variant, $role, $quantity);

            if (! $price) {
                throw ValidationException::withMessages([
                    'pricing' => "Для SKU {$variant->sku} не настроена цена для роли «{$role->name}».",
                ]);
            }

            return (float) $price->amount * $quantity;
        }), 2);
    }

    private function priceQuery(int $priceTypeId, float $quantity)
    {
        return ProductPrice::query()
            ->where('product_price_type_id', $priceTypeId)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->orderByDesc('min_quantity');
    }

    private function pickLoadedPrice(Collection $prices, int $priceTypeId, float $quantity): ?ProductPrice
    {
        return $prices
            ->filter(fn (ProductPrice $price) =>
                (int) $price->product_price_type_id === $priceTypeId
                && (float) $price->min_quantity <= $quantity
                && (! $price->valid_from || $price->valid_from->lte(now()))
                && (! $price->valid_until || $price->valid_until->gte(now()))
            )
            ->sortByDesc(fn (ProductPrice $price) => (float) $price->min_quantity)
            ->first();
    }

    private function quotePayload(
        ?ProductPrice $price,
        ProductPriceType $type,
        bool $isFrom,
        ?int $variantId,
    ): array {
        return [
            'amount' => $price ? (float) $price->amount : null,
            'old_amount' => $price?->old_amount !== null ? (float) $price->old_amount : null,
            'price_id' => $price?->id,
            'price_type_id' => $price?->product_price_type_id ?? $type->id,
            'price_type_code' => $price?->priceType?->code ?? $type->code,
            'price_type_name' => $price?->priceType?->name ?? $type->name,
            'is_from' => $isFrom,
            'variant_id' => $variantId,
        ];
    }

    private function retailPriceType(): ?ProductPriceType
    {
        return ProductPriceType::query()
            ->where('is_active', true)
            ->where('code', 'retail')
            ->first();
    }
}
