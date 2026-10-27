@extends('layouts.app')

@section('title', $preset['title'] . ' — Asia Cosmetic')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-catalog.breadcrumbs :items="$breadcrumbs" />

        <div class="mb-10 rounded-3xl bg-[#071d5d] px-6 py-10 text-white sm:px-10 sm:py-12">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-blue-200">Asia Cosmetic · Подборка</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $preset['title'] }}</h1>
            @if(!empty($preset['description']))
                <p class="mt-3 max-w-2xl text-sm leading-7 text-blue-100">{{ $preset['description'] }}</p>
            @endif
        </div>

        <div class="mb-7 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">Найдено товаров: {{ $products->total() }}</p>
            <a href="{{ route('catalog.index') }}" class="text-sm font-medium text-[#071d5d] hover:underline">Весь каталог</a>
        </div>

        @if($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($products as $product)
                    <x-catalog.product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-12">{{ $products->links() }}</div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-16 text-center">
                <h2 class="text-lg font-semibold text-slate-900">В подборке пока нет товаров</h2>
                <p class="mt-2 text-sm text-slate-500">Посмотрите другие разделы каталога.</p>
                <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-[#071d5d] px-6 py-3 text-sm font-semibold text-white hover:bg-[#0d2e84]">Перейти в каталог</a>
            </div>
        @endif
    </div>
</div>
@endsection
