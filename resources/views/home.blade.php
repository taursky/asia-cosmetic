@extends('layouts.app')

@section('title', 'Asia Cosmetic — профессиональная косметика из Азии')

@section('content')
<div class="min-h-screen bg-white text-zinc-900">
    <div class="bg-[#071d5d] text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-2.5 text-xs sm:px-6 lg:px-8">
            <nav class="hidden gap-6 text-blue-100 md:flex">
                <a href="#about" class="hover:text-white">О компании</a>
                <a href="#hit-products" class="hover:text-white">Хиты</a>
                <a href="#sale-products" class="hover:text-white">Акции</a>
                <a href="#new-products" class="hover:text-white">Новинки</a>
                <a href="#delivery" class="hover:text-white">Доставка</a>
            </nav>
            <div class="ml-auto text-blue-100">Профессиональная косметика • Asia Cosmetic</div>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto grid max-w-7xl grid-cols-[auto_1fr_auto] items-center gap-4 px-4 py-4 sm:px-6 lg:grid-cols-[200px_auto_1fr_auto] lg:px-8">
            <a href="{{ route('home') }}" class="text-xl font-semibold tracking-[-.04em] text-[#071d5d] sm:text-2xl">
                ASIA<span class="font-light">·COSMETIC</span>
            </a>

            <a href="{{ route('catalog.index') }}" class="hidden rounded-full bg-[#071d5d] px-5 py-3 text-sm font-semibold text-white hover:bg-[#0d2e84] lg:inline-flex">
                Каталог
            </a>

            <form action="{{ route('catalog.index') }}" method="get" class="col-span-3 row-start-2 flex rounded-full bg-zinc-100 p-1 lg:col-span-1 lg:row-auto">
                <input name="q" type="search" placeholder="Поиск по каталогу" class="min-w-0 flex-1 border-0 bg-transparent px-4 py-2 text-sm outline-none ring-0 placeholder:text-zinc-400 focus:ring-0">
                <button class="grid size-9 place-items-center rounded-full bg-white text-zinc-700 shadow-sm" aria-label="Поиск">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path d="m20 20-3.5-3.5" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
            </form>

            <div class="flex items-center gap-1 sm:gap-2">
                <a href="#" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Избранное">♡</a>
                <a href="#" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100" aria-label="Корзина">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5"><path d="M3 4h2l2 11h10l2-8H6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg>
                </a>
            </div>
        </div>
    </header>

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

    <footer class="bg-[#071d5d] py-12 text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <div class="text-2xl font-semibold tracking-[-.04em]">ASIA·COSMETIC</div>
                <p class="mt-4 max-w-md text-sm leading-6 text-blue-100">Профессиональная косметика из Азии для бизнеса и домашнего ухода.</p>
            </div>
            <div>
                <div class="text-sm font-semibold">Каталог</div>
                <div class="mt-4 flex flex-col gap-2 text-sm text-blue-100">
                    <a href="#new-products" class="hover:text-white">Новинки</a>
                    <a href="#hit-products" class="hover:text-white">Хиты</a>
                    <a href="#sale-products" class="hover:text-white">Скидки</a>
                </div>
            </div>
            <div>
                <div class="text-sm font-semibold">Покупателям</div>
                <div class="mt-4 flex flex-col gap-2 text-sm text-blue-100">
                    <a href="#delivery" class="hover:text-white">Доставка</a>
                    <a href="#about" class="hover:text-white">О компании</a>
                </div>
            </div>
        </div>
    </footer>
</div>
@endsection
