<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    private function baseQuery(): Builder
    {
        $locale = app()->getLocale();

        return Blog::query()
            ->active()
            ->whereHas('lang', fn (Builder $q) => $q->where('lang', $locale))
            ->with([
                'lang' => fn ($q) => $q->where('lang', $locale),
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position'),
            ]);
    }

    public function index(): View
    {
        $articles = $this->baseQuery()->latest('blogs.created_at')->paginate(9);

        return view('blog.index', compact('articles'));
    }

    public function show(string $slug): View
    {
        $article = $this->baseQuery()->whereHas('lang', fn (Builder $q) => $q->where('slug', $slug))->firstOrFail();

        $related = $this->baseQuery()
            ->whereKeyNot($article->getKey())
            ->latest('blogs.created_at')
            ->limit(3)
            ->get();

        return view('blog.show', compact('article', 'related'));
    }
}
