@extends('layouts.app')

@section('title', ($q !== '' ? 'Поиск: ' . $q : 'Поиск по каталогу') . ' — Asia Cosmetic')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-950">Главная</a>
            <span>/</span>
            <a href="{{ route('catalog.index') }}" class="hover:text-slate-950">Каталог</a>
            <span>/</span><span class="text-slate-900">Поиск</span>
        </nav>

        <h1 class="mb-5 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Поиск по каталогу</h1>
        <form action="{{ route('product.search') }}" method="get" role="search" class="mb-8 flex max-w-2xl gap-3">
            <input type="search" name="q" value="{{ $q }}" maxlength="150" placeholder="Название товара или артикул"
                   class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-slate-500 focus:ring-slate-500" />
            <button type="submit" class="rounded-xl bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white hover:opacity-90">Найти</button>
        </form>

        @if($q === '')
            <p class="text-slate-500">Введите название товара или артикул.</p>
        @elseif($products->total() === 0)
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8">
                <h2 class="text-lg font-semibold text-slate-900">Ничего не найдено</h2>
                <p class="mt-2 text-sm text-slate-500">По запросу «{{ $q }}» товаров нет. Попробуйте другое название или артикул.</p>
                <a href="{{ route('catalog.index') }}" class="mt-4 inline-block text-sm font-medium text-[#071d5d] underline">Перейти в каталог</a>
            </div>
        @else
            <p class="mb-6 text-sm text-slate-500">По запросу «{{ $q }}» найдено товаров: {{ $products->total() }}</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($products as $product)
                    <x-catalog.product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-12">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
