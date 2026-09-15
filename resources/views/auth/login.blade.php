@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] bg-zinc-50 py-12">
<div class="mx-auto max-w-xl px-4 sm:px-6">
<div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">

<h1 class="text-2xl font-semibold text-zinc-950">Вход</h1>
<p class="mt-2 text-sm text-zinc-500">Выберите удобный способ входа.</p>

@if(session('status'))<div class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif

<div class="mt-8 grid gap-8 md:grid-cols-2">
    <form method="post" action="{{ route('login.email') }}" class="space-y-4">
        @csrf
        <h2 class="font-semibold">Email и пароль</h2>
        <div><label class="text-sm">Email</label><input name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-zinc-300" required></div>
        <div><label class="text-sm">Пароль</label><input name="password" type="password" class="mt-1 w-full rounded-xl border-zinc-300" required></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Запомнить меня</label>
        @error('email')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
        <button class="w-full rounded-full bg-[#071d5d] px-5 py-3 font-semibold text-white">Войти</button>
        <a href="{{ route('password.request') }}" class="block text-center text-sm text-blue-900 hover:underline">Забыли пароль?</a>
    </form>

    <form method="post" action="{{ route('login.phone.send') }}" class="space-y-4">
        @csrf
        <h2 class="font-semibold">По телефону</h2>
        <div><label class="text-sm">Телефон</label><input name="phone" type="tel" value="{{ old('phone') }}" placeholder="+7 999 123-45-67" class="mt-1 w-full rounded-xl border-zinc-300" required></div>
        @error('phone')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
        <button class="w-full rounded-full border border-[#071d5d] px-5 py-3 font-semibold text-[#071d5d]">Получить SMS-код</button>
    </form>
</div>

<div class="mt-8 border-t pt-6 text-center text-sm text-zinc-600">Нет аккаунта? <a href="{{ route('register') }}" class="font-medium text-blue-900">Зарегистрироваться</a></div>
</div></div></div>
@endsection
