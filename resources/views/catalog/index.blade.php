@extends('layouts.app')

@section('title', 'Каталог — Asia Cosmetic')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-slate-500">Asia Cosmetic</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Каталог</h1>
        </div>

        @if($categories->isNotEmpty())
            <div class="mb-12 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($categories as $category)
                    <a
                        href="{{ route('catalog.category', $category->lang?->slug) }}"
                        class="group rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-300 hover:bg-white hover:shadow-sm"
                    >
                        <div class="text-base font-semibold text-slate-950">
                            {{ $category->lang?->name ?? 'Категория' }}
                        </div>

                        @if($category->children->isNotEmpty())
                            <div class="mt-2 text-sm text-slate-500">
                                {{ $category->children->count() }} разделов
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Все товары</h2>
                <p class="mt-1 text-sm text-slate-500">Найдено: {{ $products->total() }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($products as $product)
                <x-catalog.product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-12">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
