@extends('layouts.app')

@section('title', '404 — Страница не найдена | Asia Cosmetic')

@section('content')
    <div class="relative isolate flex min-h-[70vh] items-center justify-center overflow-hidden px-4 sm:px-6 lg:px-8">
        {{-- Декоративные градиентные пятна --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/2 top-0 h-[40rem] w-[40rem] -translate-x-1/2 -translate-y-1/3 rounded-full bg-gradient-to-br from-pink-200/60 via-rose-100/40 to-transparent blur-3xl"></div>
            <div class="absolute bottom-0 right-1/4 h-[30rem] w-[30rem] rounded-full bg-gradient-to-tr from-amber-100/50 to-transparent blur-3xl"></div>
        </div>

        <div class="mx-auto max-w-2xl text-center">
            {{-- Цифры 404 --}}
            <p class="bg-gradient-to-br from-rose-500 via-pink-500 to-amber-400 bg-clip-text font-serif text-[7rem] font-black leading-none tracking-tighter text-transparent sm:text-[10rem]">
                404
            </p>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">
                Страница не найдена
            </h1>

            <p class="mx-auto mt-4 max-w-md text-base leading-7 text-zinc-600">
                Похоже, такой страницы больше нет или она была перемещена.
                Проверьте адрес или вернитесь на главную.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ url('/') }}"
                   class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-6 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900 focus-visible:ring-offset-2 sm:w-auto">
                    На главную
                </a>
                {{--!== url()->current() ? url()->previous() : url('/') --}}
{{--                <a href="{{ url()->previous() }}"--}}
{{--                   class="inline-flex w-full items-center justify-center rounded-full border border-zinc-300 bg-white px-6 py-3 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-400 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 sm:w-auto">--}}
{{--                    ← Назад--}}
{{--                </a>--}}
            </div>

            {{-- Полезные ссылки --}}
            <div class="mt-12">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-400">
                    Может быть, вы искали
                </p>
                <nav class="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-zinc-500">
                    <a href="{{ url('/') }}" class="transition hover:text-zinc-900">Главная</a>
                    <a href="{{ url('/catalog') }}" class="transition hover:text-zinc-900">Каталог</a>
                    <a href="{{ url('/info/about') }}" class="transition hover:text-zinc-900">О нас</a>
                    <a href="{{ url('/contacts') }}" class="transition hover:text-zinc-900">Контакты</a>
                </nav>
            </div>
        </div>
    </div>
@endsection
