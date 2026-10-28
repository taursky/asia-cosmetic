<?php

namespace App\Services\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductSearch
{
    public function apply(Builder $query, string $term, string $locale): Builder
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term));
        $tokens = preg_split('/\s+/u', Str::lower($term), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_values(array_filter(array_map(
            fn (string $token) => preg_replace('/[^\pL\pN]/u', '', $token),
            $tokens
        ), fn (string $token) => mb_strlen($token) >= 3));
        $boolean = implode(' ', array_map(fn ($token) => '+' . $token . '*', array_slice($tokens, 0, 8)));
        $skuLike = '%' . addcslashes($term, '%_\\') . '%';
        $nameLike = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function (Builder $outer) use ($locale, $boolean, $skuLike, $nameLike) {
            $outer->where('products.sku', 'like', $skuLike)
                ->orWhereHas('langs', function (Builder $langs) use ($locale, $boolean, $nameLike) {
                    $langs->where('lang', $locale)->where(function (Builder $inner) use ($boolean, $nameLike) {
                        $inner->where('name', 'like', $nameLike);
                        if ($boolean !== '') {
                            $inner->orWhereRaw('MATCH(name, short_description, description) AGAINST (? IN BOOLEAN MODE)', [$boolean]);
                        }
                    });
                });
        });

        $query->orderByRaw('CASE WHEN products.sku = ? THEN 0 WHEN products.sku LIKE ? THEN 1 ELSE 2 END', [$term, $skuLike]);
        if ($boolean !== '') {
            $query->orderByRaw('(SELECT COALESCE(MAX(MATCH(pl.name, pl.short_description, pl.description) AGAINST (? IN BOOLEAN MODE)), 0) FROM product_lang pl WHERE pl.product_id = products.id AND pl.lang = ?) DESC', [$boolean, $locale]);
        }
        return $query->orderBy('products.id');
    }
}
