@extends('layouts.app')

@section('title', 'Избранное — Asia Cosmetic')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="mb-5 text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-[#071d5d]">Главная</a> / Избранное</nav>
    <div class="mb-8 flex items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Ваши подборки</p>
            <h1 class="text-3xl font-bold tracking-tight text-[#071d5d] sm:text-4xl">Избранное</h1>
            <p class="mt-2 text-sm text-slate-500">Сохранённые товары: {{ $products->total() }}</p>
        </div>
    </div>

    @if ($products->count())
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-catalog.product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    @else
        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-20 text-center">
            <div class="mb-4 text-5xl text-[#071d5d]">♡</div>
            <h2 class="text-xl font-semibold text-[#071d5d]">Пока ничего не сохранено</h2>
            <p class="mt-2 text-slate-500">Добавляйте понравившиеся товары с помощью сердечка.</p>
            <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex rounded-full bg-[#071d5d] px-6 py-3 font-semibold text-white hover:bg-[#12348d]">Перейти в каталог</a>
        </div>
    @endif
</section>
@endsection
