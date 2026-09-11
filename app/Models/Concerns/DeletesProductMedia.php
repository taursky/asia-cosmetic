<?php

namespace App\Models\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;

trait DeletesProductMedia
{
    protected static function bootDeletesProductMedia(): void
    {
        static::deleting(function (Product $product): void {
            $product->images()
                ->get()
                ->each
                ->delete();

            $product->variants()
                ->with('images')
                ->get()
                ->each(function (ProductVariant $variant): void {
                    $variant->images->each->delete();
                });
        });
    }
}
