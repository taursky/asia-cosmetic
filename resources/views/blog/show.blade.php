@extends('layouts.app')

@php
    $translation = $article->lang;
    $title = data_get($translation, 'title') ?: 'Статья';
    $content = data_get($translation, 'description') ?: '';
    $image = $article->images->first();
    $imagePath = data_get($image, 'name');
    $imageUrl = $imagePath ? (str_starts_with($imagePath, 'http') ? $imagePath : Storage::disk('public')->url($imagePath)) : null;
@endphp

@section('title', $title . ' — Asia Cosmetic')

@section('content')
<main class="bg-slate-50/70 pb-20">
    <div class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        <nav class="mb-8 text-sm text-slate-500" aria-label="Навигация">
            <a href="{{ route('home') }}" class="hover:text-[#071d5d]">Главная</a><span class="mx-2">/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-[#071d5d]">Блог</a><span class="mx-2">/</span>
            <span class="text-[#071d5d]">{{ \Illuminate\Support\Str::limit($title, 50) }}</span>
        </nav>
        <article class="mx-auto max-w-4xl">
            <header class="mb-9 text-center">
                <span class="inline-flex rounded-full bg-blue-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-[#071d5d]">Блог Asia Cosmetic</span>
                <h1 class="mt-5 text-3xl font-bold leading-tight tracking-tight text-[#071d5d] sm:text-5xl">{{ $title }}</h1>
                <time datetime="{{ $article->created_at?->toDateString() }}" class="mt-5 block text-sm text-slate-500">{{ $article->created_at?->translatedFormat('d F Y') }}</time>
            </header>
            @if($imageUrl)
                <div class="mb-9 aspect-[16/9] overflow-hidden rounded-3xl bg-slate-100 shadow-sm">
                    <img src="{{ $imageUrl }}" alt="{{ $title }}" class="h-full w-full object-cover" fetchpriority="high">
                </div>
            @endif
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-10 lg:p-14">
                <div class="blog-article-content text-base leading-8 text-slate-700">{!! $content !!}</div>
                <div class="mt-12 border-t border-slate-100 pt-7">
                    <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 font-semibold text-[#071d5d] hover:underline">← Все статьи</a>
                </div>
            </div>
        </article>
        @if($related->isNotEmpty())
            <section class="mt-16" aria-labelledby="related-heading">
                <div class="mb-7 flex items-end justify-between gap-4">
                    <h2 id="related-heading" class="text-2xl font-bold text-[#071d5d] sm:text-3xl">Читайте также</h2>
                    <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-[#071d5d] hover:underline">Все статьи →</a>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($related as $other)
                        @php
                            $otherTitle = data_get($other->lang, 'title') ?: 'Статья';
                            $otherImage = $other->images->first();
                            $otherPath = data_get($otherImage, 'name');
                            $otherUrl = $otherPath ? (str_starts_with($otherPath, 'http') ? $otherPath : Storage::disk('public')->url($otherPath)) : null;
                        @endphp
                        <a href="{{ route('blog.show', $other->lang?->slug) }}" class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:shadow-lg">
                            <div class="aspect-[16/9] overflow-hidden bg-[#071d5d]">
                                @if($otherUrl)<img src="{{ $otherUrl }}" alt="{{ $otherTitle }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else <div class="flex h-full items-center justify-center font-semibold text-white">Asia Cosmetic</div> @endif
                            </div>
                            <div class="p-5"><h3 class="line-clamp-2 font-bold text-[#071d5d]">{{ $otherTitle }}</h3></div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>
<style>
.blog-article-content > * + * { margin-top: 1.25rem; }
.blog-article-content h2 { margin-top: 2.5rem; font-size: 1.65rem; line-height: 1.3; font-weight: 750; color: #071d5d; }
.blog-article-content h3 { margin-top: 2rem; font-size: 1.3rem; font-weight: 700; color: #071d5d; }
.blog-article-content a { color: #174ca6; text-decoration: underline; text-underline-offset: 3px; }
.blog-article-content ul { list-style: disc; padding-left: 1.5rem; }
.blog-article-content ol { list-style: decimal; padding-left: 1.5rem; }
.blog-article-content li + li { margin-top: .45rem; }
.blog-article-content blockquote { border-left: 4px solid #071d5d; padding: 1rem 1.25rem; background: #f1f5f9; border-radius: 0 .75rem .75rem 0; }
.blog-article-content img { max-width: 100%; height: auto; border-radius: 1rem; margin-inline: auto; }
.blog-article-content table { display: block; overflow-x: auto; border-collapse: collapse; width: 100%; }
.blog-article-content th, .blog-article-content td { border: 1px solid #e2e8f0; padding: .65rem; }
.blog-article-content iframe { max-width: 100%; }
</style>
@endsection
