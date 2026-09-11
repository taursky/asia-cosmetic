@extends('layouts.app')

@section('title', 'Главная')

@section('content')

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

        <h1 class="text-3xl font-bold tracking-tight">
            Asia Cosmetic
        </h1>

        <p class="mt-2 text-gray-600">
            Считаем клики, Удачи.
        </p>

        <div class="mt-8">
            <div
                data-vue-component="Counter"
                data-props='@json([
                    "initial" => 10,
                ])'
            ></div>
        </div>

    </main>

@endsection
