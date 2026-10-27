@extends('layouts.app')
@section('title', 'Отзывы о товарах — Asia Cosmetic')
@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <nav class="mb-6 text-sm text-slate-500"><a href="{{ route('home') }}">Главная</a> / Отзывы</nav>
    <h1 class="mb-2 text-3xl font-semibold text-slate-950">Отзывы о товарах</h1>
    <p class="mb-10 text-sm text-slate-500">Сначала товары с наибольшим количеством положительных отзывов (4–5 звёзд).</p>
    @forelse($products as $product)
        <section class="mb-8 rounded-2xl border border-slate-200 p-5 sm:p-7">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div><a href="{{ route('catalog.product', $product->lang->slug) }}" class="text-xl font-semibold text-[#071d5d] hover:underline">{{ $product->lang->name }}</a>
                    <p class="mt-1 text-sm text-slate-500">Положительных: {{ $product->positive_reviews_count }} · Всего: {{ $product->published_reviews_count }}</p></div>
                <a href="{{ route('reviews.product', $product->lang->slug) }}" class="text-sm text-[#071d5d] hover:underline">Все отзывы</a>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach($product->reviews as $review)
                    <article class="rounded-xl bg-slate-50 p-4">
                        <div class="text-sm font-semibold text-slate-900">{{ $review->user?->name ?: 'Покупатель' }} <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}</span></div>
                        @if($review->title)<p class="mt-2 font-medium">{{ $review->title }}</p>@endif
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $review->body }}</p>
                        <a class="mt-3 inline-block text-xs font-medium text-[#071d5d] hover:underline" href="{{ route('catalog.product', $product->lang->slug) }}">Перейти к товару</a>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <p class="text-slate-500">Опубликованных отзывов пока нет.</p>
    @endforelse
    <div class="mt-10">{{ $products->links() }}</div>
</div>
@endsection
