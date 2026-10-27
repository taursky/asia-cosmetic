<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductReviewMedia extends Model
{
    protected $table = 'product_review_media';
    protected $fillable = ['path', 'type', 'mime_type', 'sort_order'];
    public function review(): BelongsTo { return $this->belongsTo(ProductReview::class, 'product_review_id'); }
    protected static function booted(): void
    {
        static::deleted(fn (self $media) => Storage::disk('public')->delete($media->path));
    }
}
