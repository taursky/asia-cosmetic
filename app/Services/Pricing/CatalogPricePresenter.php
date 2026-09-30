<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CatalogPricePresenter
{
    public function __construct(
        private readonly CustomerPriceResolver $resolver,
    ) {}

    public function decoratePaginator(LengthAwarePaginator $paginator, ?User $user): LengthAwarePaginator
    {
        $this->decorateCollection($paginator->getCollection(), $user);

        return $paginator;
    }

    public function decorateCollection(Collection $products, ?User $user): Collection
    {
        $products->each(fn (Product $product) => $this->decorateProduct($product, $user));

        return $products;
    }

    public function decorateProduct(Product $product, ?User $user): Product
    {
        $product->loadMissing([
            'prices.priceType',
            'variants.prices.priceType',
        ]);

        foreach ($product->variants as $variant) {
            $variant->setRelation('product', $product);
            $quote = $this->resolver->displayPriceForVariant($variant, $user);
            $this->apply($variant, $quote);
        }

        $quote = $this->resolver->displayPriceForProduct($product, $user);
        $this->apply($product, $quote);

        return $product;
    }

    private function apply($model, array $quote): void
    {
        $model->setAttribute('display_price', $quote['amount']);
        $model->setAttribute('display_old_price', $quote['old_amount']);
        $model->setAttribute('display_price_type_id', $quote['price_type_id']);
        $model->setAttribute('display_price_type_code', $quote['price_type_code']);
        $model->setAttribute('display_price_type_name', $quote['price_type_name']);
        $model->setAttribute('display_price_from', $quote['is_from']);
        $model->setAttribute('display_price_id', $quote['price_id']);
    }
}
