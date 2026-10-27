@extends('layouts.app')
@section('title', 'Отзывы: ' . $product->lang->name . ' — Asia Cosmetic')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <nav class="mb-6 text-sm text-slate-500"><a href="{{ route('home') }}">Главная</a> / <a href="{{ route('reviews.index') }}">Отзывы</a></nav>
    <h1 class="mb-2 text-3xl font-semibold">Отзывы о товаре</h1>
    <a href="{{ route('catalog.product', $product->lang->slug) }}" class="mb-8 inline-block font-medium text-[#071d5d] hover:underline">{{ $product->lang->name }} — перейти к товару</a>
    @forelse($reviews as $review)
        <article class="mb-4 rounded-2xl border border-slate-200 p-5">
            <div class="flex justify-between gap-3"><strong>{{ $review->user?->name ?: 'Покупатель' }}</strong><span class="text-amber-500">{{ str_repeat('★', $review->rating) }}</span></div>
            @if($review->title)<h2 class="mt-2 font-semibold">{{ $review->title }}</h2>@endif
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $review->body }}</p>
            <time class="mt-3 block text-xs text-slate-400">{{ $review->published_at?->format('d.m.Y') }}</time>
            <a class="mt-3 inline-block text-sm text-[#071d5d] hover:underline" href="{{ route('catalog.product', $product->lang->slug) }}">Посмотреть товар</a>
        </article>
    @empty
        <p class="text-slate-500">Отзывов пока нет.</p>
    @endforelse
    {{ $reviews->links() }}
</div>
@endsection
