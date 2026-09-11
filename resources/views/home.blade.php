@extends('layouts.app')

@section('title', 'Asia Cosmetic — профессиональная косметика из Азии')

@section('content')
<div class="min-h-screen bg-white text-zinc-900">
    <main>
        <section class="mx-auto max-w-7xl px-4 pb-5 pt-6 sm:px-6 lg:px-8 lg:pt-8">
            <div data-vue-component="HomeHero" data-props='@json(["slides" => $heroSlides])'></div>
        </section>

        <x-catalog.product-section id="sale-products" title="Скидки" :products="$saleProducts" />
        <x-catalog.product-section id="hit-products" title="Хиты продаж" :products="$hitProducts" />

        @if ($categories->isNotEmpty())
            <section class="bg-zinc-50 py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-8 flex items-end justify-between gap-4">
                        <div>
                            <div class="mb-2 text-xs font-semibold uppercase tracking-[.2em] text-blue-900">Каталог</div>
                            <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">Выберите направление ухода</h2>
                        </div>
                        <a href="{{ route('catalog.index') }}" class="text-sm font-medium text-blue-900 hover:underline">Весь каталог</a>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($categories as $category)
                            @php
                                $categoryName = $category->lang?->name ?? 'Категория';
                                $categorySlug = $category->lang?->slug ?? $category->id;
                            @endphp
                            <a href="{{ route('catalog.category', $categorySlug) }}" class="group relative min-h-44 overflow-hidden rounded-2xl bg-white p-5 ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg">
                                <div class="absolute -bottom-12 -right-12 size-36 rounded-full bg-blue-100 transition group-hover:scale-110"></div>
                                <div class="relative flex h-full flex-col justify-between">
                                    <span class="text-lg font-semibold tracking-tight">{{ $categoryName }}</span>
                                    <div class="mt-8 flex items-center justify-between text-sm text-zinc-500">
                                        <span>{{ $category->children->count() ? $category->children->count() . ' разделов' : 'Смотреть товары' }}</span>
                                        <span class="grid size-9 place-items-center rounded-full bg-[#071d5d] text-white">→</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <x-catalog.product-section id="new-products" title="Новинки" :products="$newProducts" />

        <section id="about" class="py-14 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:px-8">
                <div class="lg:col-span-5">
                    <div class="text-xs font-semibold uppercase tracking-[.2em] text-blue-900">Asia Cosmetic</div>
                    <h2 class="mt-3 text-3xl font-semibold leading-tight tracking-[-.035em] sm:text-4xl">Профессиональный каталог азиатской косметики</h2>
                </div>
                <div class="space-y-5 text-base leading-7 text-zinc-600 lg:col-span-7 lg:pt-6">
                    <p>Подбор средств для косметологов, салонов и домашнего ухода. Каталог построен вокруг реальных характеристик товаров, SKU-вариантов, актуальных остатков и нескольких типов цен.</p>
                    <p>Карточки автоматически используют локализованные названия, изображения с public-диска, розничные цены вариантов и данные о наличии.</p>
                </div>
            </div>
        </section>

        <section id="delivery" class="border-y border-zinc-200 bg-zinc-50 py-10">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ([
                    ['Оригинальная продукция', 'Каталог с контролируемыми поставками и данными 1С.'],
                    ['Актуальные остатки', 'Наличие берётся непосредственно из SKU-вариантов товара.'],
                    ['Оптовые цены', 'Архитектура поддерживает розничные и несколько оптовых типов цен.'],
                    ['Доставка', 'Подготовлено для подключения расчёта доставки и оформления заказа.'],
                ] as [$title, $text])
                    <div class="rounded-2xl bg-white p-5 ring-1 ring-black/5">
                        <h3 class="font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-zinc-500">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>


</div>
@endsection
