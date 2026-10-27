<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Carbon;

final class ProductBadgeService
{
    public function forProduct(Product $product): array
    {
        $badges = [];
        $price = $product->getAttribute('display_price');
        $old = $product->getAttribute('display_old_price');

        if (is_numeric($price) && is_numeric($old) && (float) $price > 0 && (float) $old > (float) $price) {
            $percent = (int) round((1 - (float) $price / (float) $old) * 100);
            if ($percent > 0) {
                $badges[] = ['text' => '-' . $percent . '%', 'type' => 'sale'];
            }
        }

        $newUntil = $product->getAttribute('badge_new_until');
        $createdAt = $product->created_at;
        $isNew = $newUntil
            ? Carbon::parse($newUntil)->endOfDay()->isFuture()
            : ($createdAt && $createdAt->greaterThanOrEqualTo(now()->subDays(30)));

        if ($isNew) {
            $badges[] = ['text' => 'NEW', 'type' => 'new'];
        }
        if ((bool) $product->getAttribute('is_hit')) {
            $badges[] = ['text' => 'ХИТ', 'type' => 'hit'];
        }

        return $badges;
    }
}
