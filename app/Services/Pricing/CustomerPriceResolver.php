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
            ->whereIn('code', ['retail', '00-000001'])
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

        if ($quotes->isNotEmpty()) {
            $quote = $quotes->sortBy('amount')->first();
            $quote['is_from'] = $quotes->pluck('amount')->unique()->count() > 1;
            return $quote;
        }

        return $this->resolveLoadedPrices($product->prices, collect(), $type, null, 1);
    }

    public function displayPriceForVariant(
        ProductVariant $variant,
        ?User $user,
        ?ProductPriceType $type = null,
    ): array {
        $type ??= $this->priceTypeFor($user);
        return $this->resolveLoadedPrices(
            $variant->prices,
            $variant->product?->prices ?? collect(),
            $type,
            $variant->id,
            1
        );
    }

    private function resolveLoadedPrices(
        Collection $variantPrices,
        Collection $productPrices,
        ProductPriceType $target,
        ?int $variantId,
        float $quantity
    ): array {
        $retail = $this->retailPriceType();
        $types = ProductPriceType::query()
            ->where('is_active', true)
            ->where('sort_order', '<=', $target->sort_order)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get();

        // First try the assigned level, then progressively lower levels.
        foreach ($types as $type) {
            if ($retail && (int) $type->sort_order === (int) $retail->sort_order && $type->id !== $retail->id) {
                continue;
            }
            $price = $this->pickLoadedPrice($variantPrices, $type->id, $quantity)
                ?? $this->pickLoadedPrice($productPrices, $type->id, $quantity);
            if (! $price) {
                continue;
            }

            $quote = $this->quotePayload($price, $type, false, $variantId);
            $retailPrice = $retail
                ? ($this->pickLoadedPrice($variantPrices, $retail->id, $quantity)
                    ?? $this->pickLoadedPrice($productPrices, $retail->id, $quantity))
                : null;
            $retailAmount = $retailPrice ? (float) $retailPrice->amount : null;
            $quote['old_amount'] = $retailAmount !== null
            && $type->id !== $retail?->id
            && $retailAmount > (float) $price->amount
                ? $retailAmount : null;
            return $quote;
        }

        // Explicit retail fallback even if its sort_order is configured unusually.
        if ($retail) {
            $price = $this->pickLoadedPrice($variantPrices, $retail->id, $quantity)
                ?? $this->pickLoadedPrice($productPrices, $retail->id, $quantity);
            if ($price) {
                $quote = $this->quotePayload($price, $retail, false, $variantId);
                $quote['old_amount'] = null;
                return $quote;
            }
        }

        return $this->quotePayload(null, $target, false, $variantId);
    }

    public function priceForVariant(ProductVariant $variant, CustomerRole $role, float $quantity = 1): ?ProductPrice
    {
        $target = $role->priceType;
        if (! $target || ! $target->is_active) {
            return null;
        }

        $types = ProductPriceType::query()
            ->where('is_active', true)
            ->where('sort_order', '<=', $target->sort_order)
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->get();

        $retail = $this->retailPriceType();
        foreach ($types as $type) {
            if ($retail && (int) $type->sort_order === (int) $retail->sort_order && $type->id !== $retail->id) {
                continue;
            }
            $price = $this->priceQuery($type->id, $quantity)
                ->where('product_id', $variant->product_id)
                ->where('product_variant_id', $variant->id)
                ->first()
                ?? $this->priceQuery($type->id, $quantity)
                    ->where('product_id', $variant->product_id)
                    ->whereNull('product_variant_id')
                    ->first();
            if ($price) {
                return $price;
            }
        }

        $retail = $this->retailPriceType();
        if (! $retail) {
            return null;
        }
        return $this->priceQuery($retail->id, $quantity)
            ->where('product_id', $variant->product_id)
            ->where('product_variant_id', $variant->id)
            ->first()
            ?? $this->priceQuery($retail->id, $quantity)
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
        $default = CustomerRole::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->with('priceType')
            ->first();
        if ($default?->priceType?->is_active) {
            return $default->priceType;
        }

        return ProductPriceType::query()
            ->where('is_active', true)
            ->whereIn('code', ['retail', '00-000001'])
            ->orderBy('sort_order')
            ->first();
    }
}
