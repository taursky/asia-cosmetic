<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductReview extends Model
{
    protected $fillable = ['product_id', 'user_id', 'rating', 'title', 'body', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'published_at' => 'datetime'];
    }

    public function media(): HasMany { return $this->hasMany(ProductReviewMedia::class, 'product_review_id')->orderBy('sort_order'); }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected static function booted(): void
    {
        static::saved(function (self $review): void {
            self::refreshProductStats((int) $review->product_id);
            if ($review->wasChanged('product_id')) {
                self::refreshProductStats((int) $review->getOriginal('product_id'));
            }
        });
        static::deleted(fn (self $review) => self::refreshProductStats((int) $review->product_id));
    }

    private static function refreshProductStats(int $productId): void
    {
        if ($productId <= 0) return;
        $stats = self::query()->published()->where('product_id', $productId)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average_rating')->first();
        Product::query()->whereKey($productId)->update([
            'reviews_count' => (int) ($stats?->total ?? 0),
            'rating' => round((float) ($stats?->average_rating ?? 0), 2),
        ]);
    }
}
