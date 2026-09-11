@extends('layouts.app')

@section('title', ($category->lang?->seo_title ?: $category->lang?->name) . ' — Asia Cosmetic')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-950">Главная</a>
            <span>/</span>
            <a href="{{ route('catalog.index') }}" class="hover:text-slate-950">Каталог</a>

            @if($category->parent?->lang)
                <span>/</span>
                <a
                    href="{{ route('catalog.category', $category->parent->lang->slug) }}"
                    class="hover:text-slate-950"
                >
                    {{ $category->parent->lang->name }}
                </a>
            @endif
        </nav>

        <div class="mb-8 max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                {{ $category->lang?->name }}
            </h1>

            @if($category->lang?->description)
                <div class="mt-4 text-sm leading-7 text-slate-600">
                    {!! nl2br(e($category->lang->description)) !!}
                </div>
            @endif
        </div>

        @if($category->children->isNotEmpty())
            <div class="mb-10 flex flex-wrap gap-2">
                @foreach($category->children as $child)
                    <a
                        href="{{ route('catalog.category', $child->lang?->slug) }}"
                        class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950"
                    >
                        {{ $child->lang?->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mb-6 text-sm text-slate-500">
            Товаров: {{ $products->total() }}
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
