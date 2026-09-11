@extends('layouts.app')

@php
    use Illuminate\Support\Facades\Storage;

    $variant = $product->variants->first();

    $images = $product->images->isNotEmpty()
        ? $product->images
        : ($variant?->images ?? collect());

    $prices = $variant?->prices?->isNotEmpty()
        ? $variant->prices
        : $product->prices;

    $retail = $prices?->first(
        fn ($price) => $price->priceType?->code === 'retail'
    );

    $currentPrice = $retail?->amount;
    $oldPrice = $retail?->old_amount;
@endphp

@section('title', ($product->lang?->seo_title ?: $product->lang?->name) . ' — Asia Cosmetic')

@section('content')
<div class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-8 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-950">Главная</a>
            <span>/</span>
            <a href="{{ route('catalog.index') }}" class="hover:text-slate-950">Каталог</a>

            @if($product->categories->first()?->lang)
                <span>/</span>
                <a
                    href="{{ route('catalog.category', $product->categories->first()->lang->slug) }}"
                    class="hover:text-slate-950"
                >
                    {{ $product->categories->first()->lang->name }}
                </a>
            @endif
        </nav>

        <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
            <section>
                <div class="overflow-hidden rounded-3xl bg-slate-50">
                    @if($images->first())
                        <img
                            src="{{ Storage::disk('public')->url($images->first()->name) }}"
                            alt="{{ $product->lang?->name }}"
                            class="aspect-square w-full object-contain p-6"
                        >
                    @else
                        <div class="flex aspect-square items-center justify-center text-slate-400">
                            Нет изображения
                        </div>
                    @endif
                </div>

                @if($images->count() > 1)
                    <div class="mt-4 grid grid-cols-5 gap-3">
                        @foreach($images->take(10) as $image)
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <img
                                    src="{{ Storage::disk('public')->url($image->name) }}"
                                    alt=""
                                    class="aspect-square w-full object-contain p-2"
                                    loading="lazy"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section>
                @if($variant?->sku ?? $product->sku)
                    <div class="text-sm text-slate-400">
                        Артикул: {{ $variant?->sku ?? $product->sku }}
                    </div>
                @endif

                <h1 class="mt-3 text-3xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-4xl">
                    {{ $product->lang?->name }}
                </h1>

                @if($product->rating)
                    <div class="mt-4 text-sm text-slate-500">
                        ★ {{ number_format((float) $product->rating, 1, ',', ' ') }}
                        @if($product->reviews_count)
                            · {{ $product->reviews_count }} отзывов
                        @endif
                    </div>
                @endif

                <div class="mt-7 flex items-baseline gap-3">
                    @if($currentPrice !== null)
                        <div class="text-3xl font-semibold text-slate-950">
                            {{ number_format((float) $currentPrice, 0, ',', ' ') }} ₽
                        </div>
                    @endif

                    @if($oldPrice && $oldPrice > $currentPrice)
                        <div class="text-lg text-slate-400 line-through">
                            {{ number_format((float) $oldPrice, 0, ',', ' ') }} ₽
                        </div>
                    @endif
                </div>

                @if($variant)
                    <div class="mt-3 text-sm font-medium {{ $variant->stock > 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $variant->stock > 0 ? 'В наличии' : 'Под заказ' }}
                    </div>
                @endif

                @if($product->variants->count() > 1)
                    <div class="mt-8">
                        <div class="mb-3 text-sm font-semibold text-slate-950">Варианты</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->variants as $item)
                                <div class="rounded-xl border border-slate-200 px-4 py-3 text-sm">
                                    <div class="font-medium text-slate-950">
                                        {{ $item->lang?->name ?: $item->sku }}
                                    </div>

                                    @if($item->optionValues->isNotEmpty())
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $item->optionValues->map(fn ($value) =>
                                                ($value->option?->lang?->name ? $value->option->lang->name . ': ' : '')
                                                . ($value->lang?->value ?? $value->code)
                                            )->implode(' · ') }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($product->lang?->short_description)
                    <div class="mt-8 rounded-2xl bg-slate-50 p-5 text-sm leading-7 text-slate-700">
                        {{ $product->lang->short_description }}
                    </div>
                @endif

                <button
                    type="button"
                    class="mt-8 w-full rounded-full bg-[#071d5d] px-6 py-4 text-sm font-semibold text-white transition hover:bg-[#0b2a82] sm:w-auto sm:min-w-64"
                >
                    Добавить в корзину
                </button>
            </section>
        </div>

        @if($product->lang?->description || $product->attributeValues->isNotEmpty())
            <div class="mt-16 grid gap-12 border-t border-slate-200 pt-10 lg:grid-cols-[1.5fr_1fr]">
                @if($product->lang?->description)
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-950">Описание</h2>
                        <div class="prose prose-slate mt-5 max-w-none">
                            {!! $product->lang->description !!}
                        </div>
                    </section>
                @endif

                @if($product->attributeValues->isNotEmpty())
                    <section>
                        <h2 class="text-2xl font-semibold text-slate-950">Характеристики</h2>

                        <dl class="mt-5 divide-y divide-slate-100">
                            @foreach($product->attributeValues as $value)
                                <div class="grid grid-cols-2 gap-4 py-3 text-sm">
                                    <dt class="text-slate-500">
                                        {{ $value->attribute?->lang?->name ?? $value->attribute?->code }}
                                    </dt>
                                    <dd class="font-medium text-slate-900">
                                        {{ $value->lang?->value ?? $value->code }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif
            </div>
        @endif

        @if($relatedProducts->isNotEmpty())
            <div class="mt-16 border-t border-slate-200 pt-10">
                <x-catalog.product-section
                    title="Похожие товары"
                    :products="$relatedProducts"
                />
            </div>
        @endif
    </div>
</div>
@endsection
