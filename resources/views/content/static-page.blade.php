@extends('layouts.app')

@section('title', 'Asia Cosmetic — профессиональная косметика из Азии')

@section('content')
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-12 lg:px-8">
        <h1>
            {{ $translation->title }}
        </h1>

        <div class="space-y-5 text-base leading-7 text-zinc-600 lg:col-span-7 lg:pt-6">
            {!! $translation->content !!}
        </div>
    </div>

@endsection
