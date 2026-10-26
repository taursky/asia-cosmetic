@extends('layouts.app')

@section('title', 'Блог — Asia Cosmetic')

@section('content')
<main class="bg-slate-50/70 pb-20">
    <div class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        <nav class="mb-7 text-sm text-slate-500" aria-label="Навигация">
            <a href="{{ route('home') }}" class="hover:text-[#071d5d]">Главная</a>
            <span class="mx-2">/</span><span class="text-[#071d5d]">Блог</span>
        </nav>
        <div class="mb-10 max-w-3xl">
            <span class="mb-3 inline-flex rounded-full bg-blue-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-[#071d5d]">Журнал Asia Cosmetic</span>
            <h1 class="text-4xl font-bold tracking-tight text-[#071d5d] sm:text-5xl">Блог о красоте и уходе</h1>
            <p class="mt-4 text-lg leading-8 text-slate-600">Советы по уходу за кожей, новинки профессиональной косметики и полезные материалы от Asia Cosmetic.</p>
        </div>

        @if($articles->isNotEmpty())
            <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($articles as $article)
                    @php
                        $translation = $article->lang;
                        $title = data_get($translation, 'title') ?: 'Статья';
                        $summary = data_get($translation, 'short_description') ?: strip_tags((string) data_get($translation, 'description', ''));
                        $image = $article->images->first();
                        $imagePath = data_get($image, 'name');
                        $imageUrl = $imagePath ? (str_starts_with($imagePath, 'http') ? $imagePath : Storage::disk('public')->url($imagePath)) : null;
                    @endphp
                    <article class="group overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <a href="{{ route('blog.show', ['slug' => $translation->slug]) }}" class="block" aria-label="{{ $title }}">
                            <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-[#071d5d] to-[#264a96] text-3xl font-bold text-white/80">Asia Cosmetic</div>
                                @endif
                            </div>
                            <div class="p-6">
                                <time datetime="{{ $article->created_at?->toDateString() }}" class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $article->created_at?->translatedFormat('d F Y') }}</time>
                                <h2 class="mt-3 line-clamp-2 text-xl font-bold leading-snug text-[#071d5d]">{{ $title }}</h2>
                                <p class="mt-3 line-clamp-3 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit(trim(strip_tags($summary)), 170) }}</p>
                                <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#071d5d]">Читать статью <span aria-hidden="true">→</span></span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
            <div class="mt-12">{{ $articles->links() }}</div>
        @else
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-20 text-center text-slate-500">Публикаций пока нет. Загляните позже.</div>
        @endif
    </div>
</main>
@endsection
