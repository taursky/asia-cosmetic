<?php

namespace App\Models;

use App\Models\Relations\LangTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StaticPage extends Model
{
    use LangTrait;
    protected $table = 'static_pages';
    protected $fillable = ['title', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $page): void {
            if (blank($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function translation(string $lang, string $fallback = 'en'): ?StaticPageLang
    {
        $translations = $this->relationLoaded('langs') ? $this->langs : $this->langs()->get();

        return $translations->firstWhere('lang', $lang)
            ?? $translations->firstWhere('lang', $fallback)
            ?? $translations->first();
    }
}
